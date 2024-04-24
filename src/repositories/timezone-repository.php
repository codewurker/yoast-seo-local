<?php

namespace Yoast\WP\Local\Repositories;

use DateTime;
use DateTimeZone;
use Exception;
use WP_Error;
use WP_Post;
use WPSEO_Local_Opening_Hours_Repository;
use Yoast\WP\Local\Conditionals\No_Conditionals;
use Yoast\WP\SEO\Initializers\Initializer_Interface;

if ( ! \class_exists( Timezone_Repository::class ) ) {

	/**
	 * Timezone_Repository class. Handles all basic needs for the plugin, like custom post_type/taxonomy.
	 */
	class Timezone_Repository implements Initializer_Interface {

		/**
		 * This trait is always required.
		 */
		use No_Conditionals;

		/**
		 * Stores the options for this plugin.
		 *
		 * @var array
		 */
		public $options = [];

		/**
		 * @var WPSEO_Local_Opening_Hours_Repository
		 */
		private $opening_hours;

		/**
		 * Initializer
		 *
		 * @return void
		 */
		public function initialize() {
			$this->options = \get_option( 'wpseo_local' );

			$this->opening_hours = new WPSEO_Local_Opening_Hours_Repository( new Options_Repository() );
		}

		/**
		 * Check whether a location is currently open or closed.
		 *
		 * @param WP_Post|int|null $post A post ID or object.
		 *
		 * @return bool|WP_Error
		 */
		public function is_location_open( $post = null ) {
			$timezone = $this->get_location_timezone( $post );

			// If the timezone for a location isn't set, try to do so.
			if ( empty( $timezone ) || \is_wp_error( $timezone ) ) {
				return '';
			}

			$post_id = null;

			if ( \wpseo_has_multiple_locations() ) {
				$post    = \get_post( $post );
				$post_id = $post->ID;
			}

			$local_datetime = $this->get_location_datetime( $post );

			if ( empty( $local_datetime ) ) {
				return new WP_Error( 'yoast-seo-local-no-location-datetime', \__( 'Cannot retrieve location current date and time', 'yoast-local-seo' ) );
			}

			$local_day     = \strtolower( $local_datetime->format( 'l' ) );
			$opening_hours = $this->opening_hours->get_opening_hours( $local_day, $post_id, $this->options );

			if ( $this->is_open_247( $post_id ) || $opening_hours['open_24h'] === 'on' ) {
				return true;
			}

			$open_from              = $opening_hours['value_from'];
			$open_to                = $opening_hours['value_to'];
			$open_second_from       = $opening_hours['value_second_from'];
			$open_second_to         = $opening_hours['value_second_to'];
			$multiple_opening_hours = $opening_hours['use_multiple_times'];

			$local_time = $local_datetime->format( 'H:i' );

			if ( $open_from !== 'closed' && ( ( $local_time >= $open_from && $local_time <= $open_to ) || ( \wpseo_check_falses( $multiple_opening_hours ) && $local_time >= $open_second_from && $local_time <= $open_second_to ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Returns the value for a timezone for a location.
		 *
		 * @param int|WP_Post|null $post Post ID or object.
		 *
		 * @return mixed
		 */
		private function get_location_timezone( $post = null ) {
			$timezone_option = ( $this->options['location_timezone'] ?? null );

			if ( ! \wpseo_has_multiple_locations() ) {
				return $timezone_option;
			}

			$post          = \get_post( $post );
			$timezone      = \get_post_meta( $post->ID, '_wpseo_business_timezone', true );
			$is_overridden = \get_post_meta( $post->ID, '_wpseo_is_overridden_business_timezone', true );

			// Default to the meta value when on a single location.
			if ( ! \wpseo_may_use_multiple_locations_shared_opening_hours() ) {
				return $timezone;
			}

			if ( ! $is_overridden ) {
				return $timezone_option;
			}

			return $timezone;
		}

		/**
		 * Retrieve the DateTIme object of a location.
		 *
		 * @param int|WP_Post|null $post Post ID or object.
		 *
		 * @return DateTime|string
		 */
		public function get_location_datetime( $post = null ) {
			$local_datetime = '';
			$timezone       = $this->get_location_timezone( $post );

			// If the timezone for a location isn't set, try to do so.
			if ( empty( $timezone ) || \is_wp_error( $timezone ) ) {
				return '';
			}

			if ( ! \is_wp_error( $timezone ) && ! empty( $timezone ) ) {
				try {
					$local_datetime = new DateTime( 'now', new DateTimeZone( $timezone ) );
				} catch ( Exception $e ) {
					return $local_datetime;
				}
			}

			return $local_datetime;
		}

		/**
		 * Retrieve a list of all the timezones in the world.
		 *
		 * @return array
		 */
		public static function get_timezones() {
			return [
				''                               => '',
				'US/Pacific'                     => \__( 'US/Pacific', 'yoast-local-seo' ),
				'US/Eastern'                     => \__( 'US/Eastern', 'yoast-local-seo' ),
				'Europe/London'                  => \__( 'Europe/London', 'yoast-local-seo' ),
				'Europe/Paris'                   => \__( 'Europe/Paris', 'yoast-local-seo' ),
				'Europe/Amsterdam'               => \__( 'Europe/Amsterdam', 'yoast-local-seo' ),
				'Europe/Berlin'                  => \__( 'Europe/Berlin', 'yoast-local-seo' ),
				'Australia/Sydney'               => \__( 'Australia/Sydney', 'yoast-local-seo' ),
				'-'                              => '------------------------------',
				'Africa/Abidjan'                 => \__( 'Africa/Abidjan', 'yoast-local-seo' ),
				'Africa/Accra'                   => \__( 'Africa/Accra', 'yoast-local-seo' ),
				'Africa/Addis_Ababa'             => \__( 'Africa/Addis_Ababa', 'yoast-local-seo' ),
				'Africa/Algiers'                 => \__( 'Africa/Algiers', 'yoast-local-seo' ),
				'Africa/Asmara'                  => \__( 'Africa/Asmara', 'yoast-local-seo' ),
				'Africa/Bamako'                  => \__( 'Africa/Bamako', 'yoast-local-seo' ),
				'Africa/Bangui'                  => \__( 'Africa/Bangui', 'yoast-local-seo' ),
				'Africa/Banjul'                  => \__( 'Africa/Banjul', 'yoast-local-seo' ),
				'Africa/Bissau'                  => \__( 'Africa/Bissau', 'yoast-local-seo' ),
				'Africa/Blantyre'                => \__( 'Africa/Blantyre', 'yoast-local-seo' ),
				'Africa/Brazzaville'             => \__( 'Africa/Brazzaville', 'yoast-local-seo' ),
				'Africa/Bujumbura'               => \__( 'Africa/Bujumbura', 'yoast-local-seo' ),
				'Africa/Cairo'                   => \__( 'Africa/Cairo', 'yoast-local-seo' ),
				'Africa/Casablanca'              => \__( 'Africa/Casablanca', 'yoast-local-seo' ),
				'Africa/Ceuta'                   => \__( 'Africa/Ceuta', 'yoast-local-seo' ),
				'Africa/Conakry'                 => \__( 'Africa/Conakry', 'yoast-local-seo' ),
				'Africa/Dakar'                   => \__( 'Africa/Dakar', 'yoast-local-seo' ),
				'Africa/Dar_es_Salaam'           => \__( 'Africa/Dar_es_Salaam', 'yoast-local-seo' ),
				'Africa/Djibouti'                => \__( 'Africa/Djibouti', 'yoast-local-seo' ),
				'Africa/Douala'                  => \__( 'Africa/Douala', 'yoast-local-seo' ),
				'Africa/El_Aaiun'                => \__( 'Africa/El_Aaiun', 'yoast-local-seo' ),
				'Africa/Freetown'                => \__( 'Africa/Freetown', 'yoast-local-seo' ),
				'Africa/Gaborone'                => \__( 'Africa/Gaborone', 'yoast-local-seo' ),
				'Africa/Harare'                  => \__( 'Africa/Harare', 'yoast-local-seo' ),
				'Africa/Johannesburg'            => \__( 'Africa/Johannesburg', 'yoast-local-seo' ),
				'Africa/Juba'                    => \__( 'Africa/Juba', 'yoast-local-seo' ),
				'Africa/Kampala'                 => \__( 'Africa/Kampala', 'yoast-local-seo' ),
				'Africa/Khartoum'                => \__( 'Africa/Khartoum', 'yoast-local-seo' ),
				'Africa/Kigali'                  => \__( 'Africa/Kigali', 'yoast-local-seo' ),
				'Africa/Kinshasa'                => \__( 'Africa/Kinshasa', 'yoast-local-seo' ),
				'Africa/Lagos'                   => \__( 'Africa/Lagos', 'yoast-local-seo' ),
				'Africa/Libreville'              => \__( 'Africa/Libreville', 'yoast-local-seo' ),
				'Africa/Lome'                    => \__( 'Africa/Lome', 'yoast-local-seo' ),
				'Africa/Luanda'                  => \__( 'Africa/Luanda', 'yoast-local-seo' ),
				'Africa/Lubumbashi'              => \__( 'Africa/Lubumbashi', 'yoast-local-seo' ),
				'Africa/Lusaka'                  => \__( 'Africa/Lusaka', 'yoast-local-seo' ),
				'Africa/Malabo'                  => \__( 'Africa/Malabo', 'yoast-local-seo' ),
				'Africa/Maputo'                  => \__( 'Africa/Maputo', 'yoast-local-seo' ),
				'Africa/Maseru'                  => \__( 'Africa/Maseru', 'yoast-local-seo' ),
				'Africa/Mbabane'                 => \__( 'Africa/Mbabane', 'yoast-local-seo' ),
				'Africa/Mogadishu'               => \__( 'Africa/Mogadishu', 'yoast-local-seo' ),
				'Africa/Monrovia'                => \__( 'Africa/Monrovia', 'yoast-local-seo' ),
				'Africa/Nairobi'                 => \__( 'Africa/Nairobi', 'yoast-local-seo' ),
				'Africa/Ndjamena'                => \__( 'Africa/Ndjamena', 'yoast-local-seo' ),
				'Africa/Niamey'                  => \__( 'Africa/Niamey', 'yoast-local-seo' ),
				'Africa/Nouakchott'              => \__( 'Africa/Nouakchott', 'yoast-local-seo' ),
				'Africa/Ouagadougou'             => \__( 'Africa/Ouagadougou', 'yoast-local-seo' ),
				'Africa/Porto-Novo'              => \__( 'Africa/Porto-Novo', 'yoast-local-seo' ),
				'Africa/Sao_Tome'                => \__( 'Africa/Sao_Tome', 'yoast-local-seo' ),
				'Africa/Tripoli'                 => \__( 'Africa/Tripoli', 'yoast-local-seo' ),
				'Africa/Tunis'                   => \__( 'Africa/Tunis', 'yoast-local-seo' ),
				'Africa/Windhoek'                => \__( 'Africa/Windhoek', 'yoast-local-seo' ),
				'America/Adak'                   => \__( 'America/Adak', 'yoast-local-seo' ),
				'America/Anchorage'              => \__( 'America/Anchorage', 'yoast-local-seo' ),
				'America/Anguilla'               => \__( 'America/Anguilla', 'yoast-local-seo' ),
				'America/Antigua'                => \__( 'America/Antigua', 'yoast-local-seo' ),
				'America/Araguaina'              => \__( 'America/Araguaina', 'yoast-local-seo' ),
				'America/Argentina/Buenos_Aires' => \__( 'America/Argentina/Buenos_Aires', 'yoast-local-seo' ),
				'America/Argentina/Catamarca'    => \__( 'America/Argentina/Catamarca', 'yoast-local-seo' ),
				'America/Argentina/Cordoba'      => \__( 'America/Argentina/Cordoba', 'yoast-local-seo' ),
				'America/Argentina/Jujuy'        => \__( 'America/Argentina/Jujuy', 'yoast-local-seo' ),
				'America/Argentina/La_Rioja'     => \__( 'America/Argentina/La_Rioja', 'yoast-local-seo' ),
				'America/Argentina/Mendoza'      => \__( 'America/Argentina/Mendoza', 'yoast-local-seo' ),
				'America/Argentina/Rio_Gallegos' => \__( 'America/Argentina/Rio_Gallegos', 'yoast-local-seo' ),
				'America/Argentina/Salta'        => \__( 'America/Argentina/Salta', 'yoast-local-seo' ),
				'America/Argentina/San_Juan'     => \__( 'America/Argentina/San_Juan', 'yoast-local-seo' ),
				'America/Argentina/San_Luis'     => \__( 'America/Argentina/San_Luis', 'yoast-local-seo' ),
				'America/Argentina/Tucuman'      => \__( 'America/Argentina/Tucuman', 'yoast-local-seo' ),
				'America/Argentina/Ushuaia'      => \__( 'America/Argentina/Ushuaia', 'yoast-local-seo' ),
				'America/Aruba'                  => \__( 'America/Aruba', 'yoast-local-seo' ),
				'America/Asuncion'               => \__( 'America/Asuncion', 'yoast-local-seo' ),
				'America/Atikokan'               => \__( 'America/Atikokan', 'yoast-local-seo' ),
				'America/Bahia'                  => \__( 'America/Bahia', 'yoast-local-seo' ),
				'America/Bahia_Banderas'         => \__( 'America/Bahia_Banderas', 'yoast-local-seo' ),
				'America/Barbados'               => \__( 'America/Barbados', 'yoast-local-seo' ),
				'America/Belem'                  => \__( 'America/Belem', 'yoast-local-seo' ),
				'America/Belize'                 => \__( 'America/Belize', 'yoast-local-seo' ),
				'America/Blanc-Sablon'           => \__( 'America/Blanc-Sablon', 'yoast-local-seo' ),
				'America/Boa_Vista'              => \__( 'America/Boa_Vista', 'yoast-local-seo' ),
				'America/Bogota'                 => \__( 'America/Bogota', 'yoast-local-seo' ),
				'America/Boise'                  => \__( 'America/Boise', 'yoast-local-seo' ),
				'America/Cambridge_Bay'          => \__( 'America/Cambridge_Bay', 'yoast-local-seo' ),
				'America/Campo_Grande'           => \__( 'America/Campo_Grande', 'yoast-local-seo' ),
				'America/Cancun'                 => \__( 'America/Cancun', 'yoast-local-seo' ),
				'America/Caracas'                => \__( 'America/Caracas', 'yoast-local-seo' ),
				'America/Cayenne'                => \__( 'America/Cayenne', 'yoast-local-seo' ),
				'America/Cayman'                 => \__( 'America/Cayman', 'yoast-local-seo' ),
				'America/Chicago'                => \__( 'America/Chicago', 'yoast-local-seo' ),
				'America/Chihuahua'              => \__( 'America/Chihuahua', 'yoast-local-seo' ),
				'America/Costa_Rica'             => \__( 'America/Costa_Rica', 'yoast-local-seo' ),
				'America/Creston'                => \__( 'America/Creston', 'yoast-local-seo' ),
				'America/Cuiaba'                 => \__( 'America/Cuiaba', 'yoast-local-seo' ),
				'America/Curacao'                => \__( 'America/Curacao', 'yoast-local-seo' ),
				'America/Danmarkshavn'           => \__( 'America/Danmarkshavn', 'yoast-local-seo' ),
				'America/Dawson'                 => \__( 'America/Dawson', 'yoast-local-seo' ),
				'America/Dawson_Creek'           => \__( 'America/Dawson_Creek', 'yoast-local-seo' ),
				'America/Denver'                 => \__( 'America/Denver', 'yoast-local-seo' ),
				'America/Detroit'                => \__( 'America/Detroit', 'yoast-local-seo' ),
				'America/Dominica'               => \__( 'America/Dominica', 'yoast-local-seo' ),
				'America/Edmonton'               => \__( 'America/Edmonton', 'yoast-local-seo' ),
				'America/Eirunepe'               => \__( 'America/Eirunepe', 'yoast-local-seo' ),
				'America/El_Salvador'            => \__( 'America/El_Salvador', 'yoast-local-seo' ),
				'America/Fortaleza'              => \__( 'America/Fortaleza', 'yoast-local-seo' ),
				'America/Glace_Bay'              => \__( 'America/Glace_Bay', 'yoast-local-seo' ),
				'America/Godthab'                => \__( 'America/Godthab', 'yoast-local-seo' ),
				'America/Goose_Bay'              => \__( 'America/Goose_Bay', 'yoast-local-seo' ),
				'America/Grand_Turk'             => \__( 'America/Grand_Turk', 'yoast-local-seo' ),
				'America/Grenada'                => \__( 'America/Grenada', 'yoast-local-seo' ),
				'America/Guadeloupe'             => \__( 'America/Guadeloupe', 'yoast-local-seo' ),
				'America/Guatemala'              => \__( 'America/Guatemala', 'yoast-local-seo' ),
				'America/Guayaquil'              => \__( 'America/Guayaquil', 'yoast-local-seo' ),
				'America/Guyana'                 => \__( 'America/Guyana', 'yoast-local-seo' ),
				'America/Halifax'                => \__( 'America/Halifax', 'yoast-local-seo' ),
				'America/Havana'                 => \__( 'America/Havana', 'yoast-local-seo' ),
				'America/Hermosillo'             => \__( 'America/Hermosillo', 'yoast-local-seo' ),
				'America/Indiana/Indianapolis'   => \__( 'America/Indiana/Indianapolis', 'yoast-local-seo' ),
				'America/Indiana/Knox'           => \__( 'America/Indiana/Knox', 'yoast-local-seo' ),
				'America/Indiana/Marengo'        => \__( 'America/Indiana/Marengo', 'yoast-local-seo' ),
				'America/Indiana/Petersburg'     => \__( 'America/Indiana/Petersburg', 'yoast-local-seo' ),
				'America/Indiana/Tell_City'      => \__( 'America/Indiana/Tell_City', 'yoast-local-seo' ),
				'America/Indiana/Vevay'          => \__( 'America/Indiana/Vevay', 'yoast-local-seo' ),
				'America/Indiana/Vincennes'      => \__( 'America/Indiana/Vincennes', 'yoast-local-seo' ),
				'America/Indiana/Winamac'        => \__( 'America/Indiana/Winamac', 'yoast-local-seo' ),
				'America/Inuvik'                 => \__( 'America/Inuvik', 'yoast-local-seo' ),
				'America/Iqaluit'                => \__( 'America/Iqaluit', 'yoast-local-seo' ),
				'America/Jamaica'                => \__( 'America/Jamaica', 'yoast-local-seo' ),
				'America/Juneau'                 => \__( 'America/Juneau', 'yoast-local-seo' ),
				'America/Kentucky/Louisville'    => \__( 'America/Kentucky/Louisville', 'yoast-local-seo' ),
				'America/Kentucky/Monticello'    => \__( 'America/Kentucky/Monticello', 'yoast-local-seo' ),
				'America/Kralendijk'             => \__( 'America/Kralendijk', 'yoast-local-seo' ),
				'America/La_Paz'                 => \__( 'America/La_Paz', 'yoast-local-seo' ),
				'America/Lima'                   => \__( 'America/Lima', 'yoast-local-seo' ),
				'America/Los_Angeles'            => \__( 'America/Los_Angeles', 'yoast-local-seo' ),
				'America/Lower_Princes'          => \__( 'America/Lower_Princes', 'yoast-local-seo' ),
				'America/Maceio'                 => \__( 'America/Maceio', 'yoast-local-seo' ),
				'America/Managua'                => \__( 'America/Managua', 'yoast-local-seo' ),
				'America/Manaus'                 => \__( 'America/Manaus', 'yoast-local-seo' ),
				'America/Marigot'                => \__( 'America/Marigot', 'yoast-local-seo' ),
				'America/Martinique'             => \__( 'America/Martinique', 'yoast-local-seo' ),
				'America/Matamoros'              => \__( 'America/Matamoros', 'yoast-local-seo' ),
				'America/Mazatlan'               => \__( 'America/Mazatlan', 'yoast-local-seo' ),
				'America/Menominee'              => \__( 'America/Menominee', 'yoast-local-seo' ),
				'America/Merida'                 => \__( 'America/Merida', 'yoast-local-seo' ),
				'America/Metlakatla'             => \__( 'America/Metlakatla', 'yoast-local-seo' ),
				'America/Mexico_City'            => \__( 'America/Mexico_City', 'yoast-local-seo' ),
				'America/Miquelon'               => \__( 'America/Miquelon', 'yoast-local-seo' ),
				'America/Moncton'                => \__( 'America/Moncton', 'yoast-local-seo' ),
				'America/Monterrey'              => \__( 'America/Monterrey', 'yoast-local-seo' ),
				'America/Montevideo'             => \__( 'America/Montevideo', 'yoast-local-seo' ),
				'America/Montreal'               => \__( 'America/Montreal', 'yoast-local-seo' ),
				'America/Montserrat'             => \__( 'America/Montserrat', 'yoast-local-seo' ),
				'America/Nassau'                 => \__( 'America/Nassau', 'yoast-local-seo' ),
				'America/New_York'               => \__( 'America/New_York', 'yoast-local-seo' ),
				'America/Nipigon'                => \__( 'America/Nipigon', 'yoast-local-seo' ),
				'America/Nome'                   => \__( 'America/Nome', 'yoast-local-seo' ),
				'America/Noronha'                => \__( 'America/Noronha', 'yoast-local-seo' ),
				'America/North_Dakota/Beulah'    => \__( 'America/North_Dakota/Beulah', 'yoast-local-seo' ),
				'America/North_Dakota/Center'    => \__( 'America/North_Dakota/Center', 'yoast-local-seo' ),
				'America/North_Dakota/New_Salem' => \__( 'America/North_Dakota/New_Salem', 'yoast-local-seo' ),
				'America/Ojinaga'                => \__( 'America/Ojinaga', 'yoast-local-seo' ),
				'America/Panama'                 => \__( 'America/Panama', 'yoast-local-seo' ),
				'America/Pangnirtung'            => \__( 'America/Pangnirtung', 'yoast-local-seo' ),
				'America/Paramaribo'             => \__( 'America/Paramaribo', 'yoast-local-seo' ),
				'America/Phoenix'                => \__( 'America/Phoenix', 'yoast-local-seo' ),
				'America/Port-au-Prince'         => \__( 'America/Port-au-Prince', 'yoast-local-seo' ),
				'America/Port_of_Spain'          => \__( 'America/Port_of_Spain', 'yoast-local-seo' ),
				'America/Porto_Velho'            => \__( 'America/Porto_Velho', 'yoast-local-seo' ),
				'America/Puerto_Rico'            => \__( 'America/Puerto_Rico', 'yoast-local-seo' ),
				'America/Rainy_River'            => \__( 'America/Rainy_River', 'yoast-local-seo' ),
				'America/Rankin_Inlet'           => \__( 'America/Rankin_Inlet', 'yoast-local-seo' ),
				'America/Recife'                 => \__( 'America/Recife', 'yoast-local-seo' ),
				'America/Regina'                 => \__( 'America/Regina', 'yoast-local-seo' ),
				'America/Resolute'               => \__( 'America/Resolute', 'yoast-local-seo' ),
				'America/Rio_Branco'             => \__( 'America/Rio_Branco', 'yoast-local-seo' ),
				'America/Santa_Isabel'           => \__( 'America/Santa_Isabel', 'yoast-local-seo' ),
				'America/Santarem'               => \__( 'America/Santarem', 'yoast-local-seo' ),
				'America/Santiago'               => \__( 'America/Santiago', 'yoast-local-seo' ),
				'America/Santo_Domingo'          => \__( 'America/Santo_Domingo', 'yoast-local-seo' ),
				'America/Sao_Paulo'              => \__( 'America/Sao_Paulo', 'yoast-local-seo' ),
				'America/Scoresbysund'           => \__( 'America/Scoresbysund', 'yoast-local-seo' ),
				'America/Shiprock'               => \__( 'America/Shiprock', 'yoast-local-seo' ),
				'America/Sitka'                  => \__( 'America/Sitka', 'yoast-local-seo' ),
				'America/St_Barthelemy'          => \__( 'America/St_Barthelemy', 'yoast-local-seo' ),
				'America/St_Johns'               => \__( 'America/St_Johns', 'yoast-local-seo' ),
				'America/St_Kitts'               => \__( 'America/St_Kitts', 'yoast-local-seo' ),
				'America/St_Lucia'               => \__( 'America/St_Lucia', 'yoast-local-seo' ),
				'America/St_Thomas'              => \__( 'America/St_Thomas', 'yoast-local-seo' ),
				'America/St_Vincent'             => \__( 'America/St_Vincent', 'yoast-local-seo' ),
				'America/Swift_Current'          => \__( 'America/Swift_Current', 'yoast-local-seo' ),
				'America/Tegucigalpa'            => \__( 'America/Tegucigalpa', 'yoast-local-seo' ),
				'America/Thule'                  => \__( 'America/Thule', 'yoast-local-seo' ),
				'America/Thunder_Bay'            => \__( 'America/Thunder_Bay', 'yoast-local-seo' ),
				'America/Tijuana'                => \__( 'America/Tijuana', 'yoast-local-seo' ),
				'America/Toronto'                => \__( 'America/Toronto', 'yoast-local-seo' ),
				'America/Tortola'                => \__( 'America/Tortola', 'yoast-local-seo' ),
				'America/Vancouver'              => \__( 'America/Vancouver', 'yoast-local-seo' ),
				'America/Whitehorse'             => \__( 'America/Whitehorse', 'yoast-local-seo' ),
				'America/Winnipeg'               => \__( 'America/Winnipeg', 'yoast-local-seo' ),
				'America/Yakutat'                => \__( 'America/Yakutat', 'yoast-local-seo' ),
				'America/Yellowknife'            => \__( 'America/Yellowknife', 'yoast-local-seo' ),
				'Antarctica/Casey'               => \__( 'Antarctica/Casey', 'yoast-local-seo' ),
				'Antarctica/Davis'               => \__( 'Antarctica/Davis', 'yoast-local-seo' ),
				'Antarctica/DumontDUrville'      => \__( 'Antarctica/DumontDUrville', 'yoast-local-seo' ),
				'Antarctica/Macquarie'           => \__( 'Antarctica/Macquarie', 'yoast-local-seo' ),
				'Antarctica/Mawson'              => \__( 'Antarctica/Mawson', 'yoast-local-seo' ),
				'Antarctica/McMurdo'             => \__( 'Antarctica/McMurdo', 'yoast-local-seo' ),
				'Antarctica/Palmer'              => \__( 'Antarctica/Palmer', 'yoast-local-seo' ),
				'Antarctica/Rothera'             => \__( 'Antarctica/Rothera', 'yoast-local-seo' ),
				'Antarctica/South_Pole'          => \__( 'Antarctica/South_Pole', 'yoast-local-seo' ),
				'Antarctica/Syowa'               => \__( 'Antarctica/Syowa', 'yoast-local-seo' ),
				'Antarctica/Vostok'              => \__( 'Antarctica/Vostok', 'yoast-local-seo' ),
				'Arctic/Longyearbyen'            => \__( 'Arctic/Longyearbyen', 'yoast-local-seo' ),
				'Asia/Aden'                      => \__( 'Asia/Aden', 'yoast-local-seo' ),
				'Asia/Almaty'                    => \__( 'Asia/Almaty', 'yoast-local-seo' ),
				'Asia/Amman'                     => \__( 'Asia/Amman', 'yoast-local-seo' ),
				'Asia/Anadyr'                    => \__( 'Asia/Anadyr', 'yoast-local-seo' ),
				'Asia/Aqtau'                     => \__( 'Asia/Aqtau', 'yoast-local-seo' ),
				'Asia/Aqtobe'                    => \__( 'Asia/Aqtobe', 'yoast-local-seo' ),
				'Asia/Ashgabat'                  => \__( 'Asia/Ashgabat', 'yoast-local-seo' ),
				'Asia/Baghdad'                   => \__( 'Asia/Baghdad', 'yoast-local-seo' ),
				'Asia/Bahrain'                   => \__( 'Asia/Bahrain', 'yoast-local-seo' ),
				'Asia/Baku'                      => \__( 'Asia/Baku', 'yoast-local-seo' ),
				'Asia/Bangkok'                   => \__( 'Asia/Bangkok', 'yoast-local-seo' ),
				'Asia/Beirut'                    => \__( 'Asia/Beirut', 'yoast-local-seo' ),
				'Asia/Bishkek'                   => \__( 'Asia/Bishkek', 'yoast-local-seo' ),
				'Asia/Brunei'                    => \__( 'Asia/Brunei', 'yoast-local-seo' ),
				'Asia/Choibalsan'                => \__( 'Asia/Choibalsan', 'yoast-local-seo' ),
				'Asia/Chongqing'                 => \__( 'Asia/Chongqing', 'yoast-local-seo' ),
				'Asia/Colombo'                   => \__( 'Asia/Colombo', 'yoast-local-seo' ),
				'Asia/Damascus'                  => \__( 'Asia/Damascus', 'yoast-local-seo' ),
				'Asia/Dhaka'                     => \__( 'Asia/Dhaka', 'yoast-local-seo' ),
				'Asia/Dili'                      => \__( 'Asia/Dili', 'yoast-local-seo' ),
				'Asia/Dubai'                     => \__( 'Asia/Dubai', 'yoast-local-seo' ),
				'Asia/Dushanbe'                  => \__( 'Asia/Dushanbe', 'yoast-local-seo' ),
				'Asia/Gaza'                      => \__( 'Asia/Gaza', 'yoast-local-seo' ),
				'Asia/Harbin'                    => \__( 'Asia/Harbin', 'yoast-local-seo' ),
				'Asia/Hebron'                    => \__( 'Asia/Hebron', 'yoast-local-seo' ),
				'Asia/Ho_Chi_Minh'               => \__( 'Asia/Ho_Chi_Minh', 'yoast-local-seo' ),
				'Asia/Hong_Kong'                 => \__( 'Asia/Hong_Kong', 'yoast-local-seo' ),
				'Asia/Hovd'                      => \__( 'Asia/Hovd', 'yoast-local-seo' ),
				'Asia/Irkutsk'                   => \__( 'Asia/Irkutsk', 'yoast-local-seo' ),
				'Asia/Jakarta'                   => \__( 'Asia/Jakarta', 'yoast-local-seo' ),
				'Asia/Jayapura'                  => \__( 'Asia/Jayapura', 'yoast-local-seo' ),
				'Asia/Jerusalem'                 => \__( 'Asia/Jerusalem', 'yoast-local-seo' ),
				'Asia/Kabul'                     => \__( 'Asia/Kabul', 'yoast-local-seo' ),
				'Asia/Kamchatka'                 => \__( 'Asia/Kamchatka', 'yoast-local-seo' ),
				'Asia/Karachi'                   => \__( 'Asia/Karachi', 'yoast-local-seo' ),
				'Asia/Kashgar'                   => \__( 'Asia/Kashgar', 'yoast-local-seo' ),
				'Asia/Kathmandu'                 => \__( 'Asia/Kathmandu', 'yoast-local-seo' ),
				'Asia/Kolkata'                   => \__( 'Asia/Kolkata', 'yoast-local-seo' ),
				'Asia/Krasnoyarsk'               => \__( 'Asia/Krasnoyarsk', 'yoast-local-seo' ),
				'Asia/Kuala_Lumpur'              => \__( 'Asia/Kuala_Lumpur', 'yoast-local-seo' ),
				'Asia/Kuching'                   => \__( 'Asia/Kuching', 'yoast-local-seo' ),
				'Asia/Kuwait'                    => \__( 'Asia/Kuwait', 'yoast-local-seo' ),
				'Asia/Macau'                     => \__( 'Asia/Macau', 'yoast-local-seo' ),
				'Asia/Magadan'                   => \__( 'Asia/Magadan', 'yoast-local-seo' ),
				'Asia/Makassar'                  => \__( 'Asia/Makassar', 'yoast-local-seo' ),
				'Asia/Manila'                    => \__( 'Asia/Manila', 'yoast-local-seo' ),
				'Asia/Muscat'                    => \__( 'Asia/Muscat', 'yoast-local-seo' ),
				'Asia/Nicosia'                   => \__( 'Asia/Nicosia', 'yoast-local-seo' ),
				'Asia/Novokuznetsk'              => \__( 'Asia/Novokuznetsk', 'yoast-local-seo' ),
				'Asia/Novosibirsk'               => \__( 'Asia/Novosibirsk', 'yoast-local-seo' ),
				'Asia/Omsk'                      => \__( 'Asia/Omsk', 'yoast-local-seo' ),
				'Asia/Oral'                      => \__( 'Asia/Oral', 'yoast-local-seo' ),
				'Asia/Phnom_Penh'                => \__( 'Asia/Phnom_Penh', 'yoast-local-seo' ),
				'Asia/Pontianak'                 => \__( 'Asia/Pontianak', 'yoast-local-seo' ),
				'Asia/Pyongyang'                 => \__( 'Asia/Pyongyang', 'yoast-local-seo' ),
				'Asia/Qatar'                     => \__( 'Asia/Qatar', 'yoast-local-seo' ),
				'Asia/Qyzylorda'                 => \__( 'Asia/Qyzylorda', 'yoast-local-seo' ),
				'Asia/Rangoon'                   => \__( 'Asia/Rangoon', 'yoast-local-seo' ),
				'Asia/Riyadh'                    => \__( 'Asia/Riyadh', 'yoast-local-seo' ),
				'Asia/Sakhalin'                  => \__( 'Asia/Sakhalin', 'yoast-local-seo' ),
				'Asia/Samarkand'                 => \__( 'Asia/Samarkand', 'yoast-local-seo' ),
				'Asia/Seoul'                     => \__( 'Asia/Seoul', 'yoast-local-seo' ),
				'Asia/Shanghai'                  => \__( 'Asia/Shanghai', 'yoast-local-seo' ),
				'Asia/Singapore'                 => \__( 'Asia/Singapore', 'yoast-local-seo' ),
				'Asia/Taipei'                    => \__( 'Asia/Taipei', 'yoast-local-seo' ),
				'Asia/Tashkent'                  => \__( 'Asia/Tashkent', 'yoast-local-seo' ),
				'Asia/Tbilisi'                   => \__( 'Asia/Tbilisi', 'yoast-local-seo' ),
				'Asia/Tehran'                    => \__( 'Asia/Tehran', 'yoast-local-seo' ),
				'Asia/Thimphu'                   => \__( 'Asia/Thimphu', 'yoast-local-seo' ),
				'Asia/Tokyo'                     => \__( 'Asia/Tokyo', 'yoast-local-seo' ),
				'Asia/Ulaanbaatar'               => \__( 'Asia/Ulaanbaatar', 'yoast-local-seo' ),
				'Asia/Urumqi'                    => \__( 'Asia/Urumqi', 'yoast-local-seo' ),
				'Asia/Vientiane'                 => \__( 'Asia/Vientiane', 'yoast-local-seo' ),
				'Asia/Vladivostok'               => \__( 'Asia/Vladivostok', 'yoast-local-seo' ),
				'Asia/Yakutsk'                   => \__( 'Asia/Yakutsk', 'yoast-local-seo' ),
				'Asia/Yekaterinburg'             => \__( 'Asia/Yekaterinburg', 'yoast-local-seo' ),
				'Asia/Yerevan'                   => \__( 'Asia/Yerevan', 'yoast-local-seo' ),
				'Atlantic/Azores'                => \__( 'Atlantic/Azores', 'yoast-local-seo' ),
				'Atlantic/Bermuda'               => \__( 'Atlantic/Bermuda', 'yoast-local-seo' ),
				'Atlantic/Canary'                => \__( 'Atlantic/Canary', 'yoast-local-seo' ),
				'Atlantic/Cape_Verde'            => \__( 'Atlantic/Cape_Verde', 'yoast-local-seo' ),
				'Atlantic/Faroe'                 => \__( 'Atlantic/Faroe', 'yoast-local-seo' ),
				'Atlantic/Madeira'               => \__( 'Atlantic/Madeira', 'yoast-local-seo' ),
				'Atlantic/Reykjavik'             => \__( 'Atlantic/Reykjavik', 'yoast-local-seo' ),
				'Atlantic/South_Georgia'         => \__( 'Atlantic/South_Georgia', 'yoast-local-seo' ),
				'Atlantic/St_Helena'             => \__( 'Atlantic/St_Helena', 'yoast-local-seo' ),
				'Atlantic/Stanley'               => \__( 'Atlantic/Stanley', 'yoast-local-seo' ),
				'Australia/Adelaide'             => \__( 'Australia/Adelaide', 'yoast-local-seo' ),
				'Australia/Brisbane'             => \__( 'Australia/Brisbane', 'yoast-local-seo' ),
				'Australia/Broken_Hill'          => \__( 'Australia/Broken_Hill', 'yoast-local-seo' ),
				'Australia/Currie'               => \__( 'Australia/Currie', 'yoast-local-seo' ),
				'Australia/Darwin'               => \__( 'Australia/Darwin', 'yoast-local-seo' ),
				'Australia/Eucla'                => \__( 'Australia/Eucla', 'yoast-local-seo' ),
				'Australia/Hobart'               => \__( 'Australia/Hobart', 'yoast-local-seo' ),
				'Australia/Lindeman'             => \__( 'Australia/Lindeman', 'yoast-local-seo' ),
				'Australia/Lord_Howe'            => \__( 'Australia/Lord_Howe', 'yoast-local-seo' ),
				'Australia/Melbourne'            => \__( 'Australia/Melbourne', 'yoast-local-seo' ),
				'Australia/Perth'                => \__( 'Australia/Perth', 'yoast-local-seo' ),
				'Canada/Atlantic'                => \__( 'Canada/Atlantic', 'yoast-local-seo' ),
				'Canada/Central'                 => \__( 'Canada/Central', 'yoast-local-seo' ),
				'Canada/Eastern'                 => \__( 'Canada/Eastern', 'yoast-local-seo' ),
				'Canada/Mountain'                => \__( 'Canada/Mountain', 'yoast-local-seo' ),
				'Canada/Newfoundland'            => \__( 'Canada/Newfoundland', 'yoast-local-seo' ),
				'Canada/Pacific'                 => \__( 'Canada/Pacific', 'yoast-local-seo' ),
				'Europe/Andorra'                 => \__( 'Europe/Andorra', 'yoast-local-seo' ),
				'Europe/Athens'                  => \__( 'Europe/Athens', 'yoast-local-seo' ),
				'Europe/Belgrade'                => \__( 'Europe/Belgrade', 'yoast-local-seo' ),
				'Europe/Bratislava'              => \__( 'Europe/Bratislava', 'yoast-local-seo' ),
				'Europe/Brussels'                => \__( 'Europe/Brussels', 'yoast-local-seo' ),
				'Europe/Bucharest'               => \__( 'Europe/Bucharest', 'yoast-local-seo' ),
				'Europe/Budapest'                => \__( 'Europe/Budapest', 'yoast-local-seo' ),
				'Europe/Chisinau'                => \__( 'Europe/Chisinau', 'yoast-local-seo' ),
				'Europe/Copenhagen'              => \__( 'Europe/Copenhagen', 'yoast-local-seo' ),
				'Europe/Dublin'                  => \__( 'Europe/Dublin', 'yoast-local-seo' ),
				'Europe/Gibraltar'               => \__( 'Europe/Gibraltar', 'yoast-local-seo' ),
				'Europe/Guernsey'                => \__( 'Europe/Guernsey', 'yoast-local-seo' ),
				'Europe/Helsinki'                => \__( 'Europe/Helsinki', 'yoast-local-seo' ),
				'Europe/Isle_of_Man'             => \__( 'Europe/Isle_of_Man', 'yoast-local-seo' ),
				'Europe/Istanbul'                => \__( 'Europe/Istanbul', 'yoast-local-seo' ),
				'Europe/Jersey'                  => \__( 'Europe/Jersey', 'yoast-local-seo' ),
				'Europe/Kaliningrad'             => \__( 'Europe/Kaliningrad', 'yoast-local-seo' ),
				'Europe/Kiev'                    => \__( 'Europe/Kiev', 'yoast-local-seo' ),
				'Europe/Lisbon'                  => \__( 'Europe/Lisbon', 'yoast-local-seo' ),
				'Europe/Ljubljana'               => \__( 'Europe/Ljubljana', 'yoast-local-seo' ),
				'Europe/Luxembourg'              => \__( 'Europe/Luxembourg', 'yoast-local-seo' ),
				'Europe/Madrid'                  => \__( 'Europe/Madrid', 'yoast-local-seo' ),
				'Europe/Malta'                   => \__( 'Europe/Malta', 'yoast-local-seo' ),
				'Europe/Mariehamn'               => \__( 'Europe/Mariehamn', 'yoast-local-seo' ),
				'Europe/Minsk'                   => \__( 'Europe/Minsk', 'yoast-local-seo' ),
				'Europe/Monaco'                  => \__( 'Europe/Monaco', 'yoast-local-seo' ),
				'Europe/Moscow'                  => \__( 'Europe/Moscow', 'yoast-local-seo' ),
				'Europe/Oslo'                    => \__( 'Europe/Oslo', 'yoast-local-seo' ),
				'Europe/Podgorica'               => \__( 'Europe/Podgorica', 'yoast-local-seo' ),
				'Europe/Prague'                  => \__( 'Europe/Prague', 'yoast-local-seo' ),
				'Europe/Riga'                    => \__( 'Europe/Riga', 'yoast-local-seo' ),
				'Europe/Rome'                    => \__( 'Europe/Rome', 'yoast-local-seo' ),
				'Europe/Samara'                  => \__( 'Europe/Samara', 'yoast-local-seo' ),
				'Europe/San_Marino'              => \__( 'Europe/San_Marino', 'yoast-local-seo' ),
				'Europe/Sarajevo'                => \__( 'Europe/Sarajevo', 'yoast-local-seo' ),
				'Europe/Simferopol'              => \__( 'Europe/Simferopol', 'yoast-local-seo' ),
				'Europe/Skopje'                  => \__( 'Europe/Skopje', 'yoast-local-seo' ),
				'Europe/Sofia'                   => \__( 'Europe/Sofia', 'yoast-local-seo' ),
				'Europe/Stockholm'               => \__( 'Europe/Stockholm', 'yoast-local-seo' ),
				'Europe/Tallinn'                 => \__( 'Europe/Tallinn', 'yoast-local-seo' ),
				'Europe/Tirane'                  => \__( 'Europe/Tirane', 'yoast-local-seo' ),
				'Europe/Uzhgorod'                => \__( 'Europe/Uzhgorod', 'yoast-local-seo' ),
				'Europe/Vaduz'                   => \__( 'Europe/Vaduz', 'yoast-local-seo' ),
				'Europe/Vatican'                 => \__( 'Europe/Vatican', 'yoast-local-seo' ),
				'Europe/Vienna'                  => \__( 'Europe/Vienna', 'yoast-local-seo' ),
				'Europe/Vilnius'                 => \__( 'Europe/Vilnius', 'yoast-local-seo' ),
				'Europe/Volgograd'               => \__( 'Europe/Volgograd', 'yoast-local-seo' ),
				'Europe/Warsaw'                  => \__( 'Europe/Warsaw', 'yoast-local-seo' ),
				'Europe/Zagreb'                  => \__( 'Europe/Zagreb', 'yoast-local-seo' ),
				'Europe/Zaporozhye'              => \__( 'Europe/Zaporozhye', 'yoast-local-seo' ),
				'Europe/Zurich'                  => \__( 'Europe/Zurich', 'yoast-local-seo' ),
				'GMT'                            => \__( 'GMT', 'yoast-local-seo' ),
				'Indian/Antananarivo'            => \__( 'Indian/Antananarivo', 'yoast-local-seo' ),
				'Indian/Chagos'                  => \__( 'Indian/Chagos', 'yoast-local-seo' ),
				'Indian/Christmas'               => \__( 'Indian/Christmas', 'yoast-local-seo' ),
				'Indian/Cocos'                   => \__( 'Indian/Cocos', 'yoast-local-seo' ),
				'Indian/Comoro'                  => \__( 'Indian/Comoro', 'yoast-local-seo' ),
				'Indian/Kerguelen'               => \__( 'Indian/Kerguelen', 'yoast-local-seo' ),
				'Indian/Mahe'                    => \__( 'Indian/Mahe', 'yoast-local-seo' ),
				'Indian/Maldives'                => \__( 'Indian/Maldives', 'yoast-local-seo' ),
				'Indian/Mauritius'               => \__( 'Indian/Mauritius', 'yoast-local-seo' ),
				'Indian/Mayotte'                 => \__( 'Indian/Mayotte', 'yoast-local-seo' ),
				'Indian/Reunion'                 => \__( 'Indian/Reunion', 'yoast-local-seo' ),
				'Pacific/Apia'                   => \__( 'Pacific/Apia', 'yoast-local-seo' ),
				'Pacific/Auckland'               => \__( 'Pacific/Auckland', 'yoast-local-seo' ),
				'Pacific/Chatham'                => \__( 'Pacific/Chatham', 'yoast-local-seo' ),
				'Pacific/Chuuk'                  => \__( 'Pacific/Chuuk', 'yoast-local-seo' ),
				'Pacific/Easter'                 => \__( 'Pacific/Easter', 'yoast-local-seo' ),
				'Pacific/Efate'                  => \__( 'Pacific/Efate', 'yoast-local-seo' ),
				'Pacific/Enderbury'              => \__( 'Pacific/Enderbury', 'yoast-local-seo' ),
				'Pacific/Fakaofo'                => \__( 'Pacific/Fakaofo', 'yoast-local-seo' ),
				'Pacific/Fiji'                   => \__( 'Pacific/Fiji', 'yoast-local-seo' ),
				'Pacific/Funafuti'               => \__( 'Pacific/Funafuti', 'yoast-local-seo' ),
				'Pacific/Galapagos'              => \__( 'Pacific/Galapagos', 'yoast-local-seo' ),
				'Pacific/Gambier'                => \__( 'Pacific/Gambier', 'yoast-local-seo' ),
				'Pacific/Guadalcanal'            => \__( 'Pacific/Guadalcanal', 'yoast-local-seo' ),
				'Pacific/Guam'                   => \__( 'Pacific/Guam', 'yoast-local-seo' ),
				'Pacific/Honolulu'               => \__( 'Pacific/Honolulu', 'yoast-local-seo' ),
				'Pacific/Johnston'               => \__( 'Pacific/Johnston', 'yoast-local-seo' ),
				'Pacific/Kiritimati'             => \__( 'Pacific/Kiritimati', 'yoast-local-seo' ),
				'Pacific/Kosrae'                 => \__( 'Pacific/Kosrae', 'yoast-local-seo' ),
				'Pacific/Kwajalein'              => \__( 'Pacific/Kwajalein', 'yoast-local-seo' ),
				'Pacific/Majuro'                 => \__( 'Pacific/Majuro', 'yoast-local-seo' ),
				'Pacific/Marquesas'              => \__( 'Pacific/Marquesas', 'yoast-local-seo' ),
				'Pacific/Midway'                 => \__( 'Pacific/Midway', 'yoast-local-seo' ),
				'Pacific/Nauru'                  => \__( 'Pacific/Nauru', 'yoast-local-seo' ),
				'Pacific/Niue'                   => \__( 'Pacific/Niue', 'yoast-local-seo' ),
				'Pacific/Norfolk'                => \__( 'Pacific/Norfolk', 'yoast-local-seo' ),
				'Pacific/Noumea'                 => \__( 'Pacific/Noumea', 'yoast-local-seo' ),
				'Pacific/Pago_Pago'              => \__( 'Pacific/Pago_Pago', 'yoast-local-seo' ),
				'Pacific/Palau'                  => \__( 'Pacific/Palau', 'yoast-local-seo' ),
				'Pacific/Pitcairn'               => \__( 'Pacific/Pitcairn', 'yoast-local-seo' ),
				'Pacific/Pohnpei'                => \__( 'Pacific/Pohnpei', 'yoast-local-seo' ),
				'Pacific/Port_Moresby'           => \__( 'Pacific/Port_Moresby', 'yoast-local-seo' ),
				'Pacific/Rarotonga'              => \__( 'Pacific/Rarotonga', 'yoast-local-seo' ),
				'Pacific/Saipan'                 => \__( 'Pacific/Saipan', 'yoast-local-seo' ),
				'Pacific/Tahiti'                 => \__( 'Pacific/Tahiti', 'yoast-local-seo' ),
				'Pacific/Tarawa'                 => \__( 'Pacific/Tarawa', 'yoast-local-seo' ),
				'Pacific/Tongatapu'              => \__( 'Pacific/Tongatapu', 'yoast-local-seo' ),
				'Pacific/Wake'                   => \__( 'Pacific/Wake', 'yoast-local-seo' ),
				'Pacific/Wallis'                 => \__( 'Pacific/Wallis', 'yoast-local-seo' ),
				'US/Alaska'                      => \__( 'US/Alaska', 'yoast-local-seo' ),
				'US/Arizona'                     => \__( 'US/Arizona', 'yoast-local-seo' ),
				'US/Central'                     => \__( 'US/Central', 'yoast-local-seo' ),
				'US/Hawaii'                      => \__( 'US/Hawaii', 'yoast-local-seo' ),
				'US/Mountain'                    => \__( 'US/Mountain', 'yoast-local-seo' ),
				'UTC'                            => \__( 'UTC', 'yoast-local-seo' ),
			];
		}

		/**
		 * Determines whether the location is open 24/7.
		 *
		 * @param int $location_id The location ID.
		 *
		 * @return bool Whether the location is open 24/7.
		 */
		private function is_open_247( $location_id ) {
			if ( ! \wpseo_has_multiple_locations() ) {
				$options = \get_option( 'wpseo_local' );

				return ( isset( $options['open_247'] ) && $options['open_247'] === 'on' );
			}

			$open_247_overridden = \get_post_meta( $location_id, '_wpseo_open_247_overridden', true ) === 'on';
			$open_247            = \get_post_meta( $location_id, '_wpseo_open_247', true );

			return ( $open_247_overridden && $open_247 === 'on' );
		}
	}
}
