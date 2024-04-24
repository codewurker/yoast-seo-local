<?php

namespace Yoast\WP\SEO\Local\Generated;

use YoastSEO_Vendor\Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use YoastSEO_Vendor\Symfony\Component\DependencyInjection\ContainerInterface;
use YoastSEO_Vendor\Symfony\Component\DependencyInjection\Container;
use YoastSEO_Vendor\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use YoastSEO_Vendor\Symfony\Component\DependencyInjection\Exception\LogicException;
use YoastSEO_Vendor\Symfony\Component\DependencyInjection\Exception\RuntimeException;
use YoastSEO_Vendor\Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 *
 * @final since Symfony 3.3
 */
class Cached_Container extends Container
{
    private $parameters = [];
    private $targetDirs = [];

    public function __construct()
    {
        $this->services = [];
        $this->normalizedIds = [
            'yoast\\wp\\local\\builders\\locations_repository_builder' => 'Yoast\\WP\\Local\\Builders\\Locations_Repository_Builder',
            'yoast\\wp\\local\\conditionals\\admin_conditional' => 'Yoast\\WP\\Local\\Conditionals\\Admin_Conditional',
            'yoast\\wp\\local\\conditionals\\multiple_locations_conditional' => 'Yoast\\WP\\Local\\Conditionals\\Multiple_Locations_Conditional',
            'yoast\\wp\\local\\formatters\\address_formatter' => 'Yoast\\WP\\Local\\Formatters\\Address_Formatter',
            'yoast\\wp\\local\\integrations\\front_end_integration' => 'Yoast\\WP\\Local\\Integrations\\Front_End_Integration',
            'yoast\\wp\\local\\integrations\\local_pickup_notification' => 'Yoast\\WP\\Local\\Integrations\\Local_Pickup_Notification',
            'yoast\\wp\\local\\integrations\\multiple_locations_watcher' => 'Yoast\\WP\\Local\\Integrations\\Multiple_Locations_Watcher',
            'yoast\\wp\\local\\integrations\\translationspress' => 'Yoast\\WP\\Local\\Integrations\\TranslationsPress',
            'yoast\\wp\\local\\posttype\\posttype' => 'Yoast\\WP\\Local\\PostType\\PostType',
            'yoast\\wp\\local\\repositories\\api_keys_repository' => 'Yoast\\WP\\Local\\Repositories\\Api_Keys_Repository',
            'yoast\\wp\\local\\repositories\\business_types_repository' => 'Yoast\\WP\\Local\\Repositories\\Business_Types_Repository',
            'yoast\\wp\\local\\repositories\\locations_repository' => 'Yoast\\WP\\Local\\Repositories\\Locations_Repository',
            'yoast\\wp\\local\\repositories\\options_repository' => 'Yoast\\WP\\Local\\Repositories\\Options_Repository',
            'yoast\\wp\\local\\repositories\\timezone_repository' => 'Yoast\\WP\\Local\\Repositories\\Timezone_Repository',
            'yoast\\wp\\local\\tools\\export' => 'Yoast\\WP\\Local\\Tools\\Export',
            'yoast\\wp\\local\\tools\\import' => 'Yoast\\WP\\Local\\Tools\\Import',
            'yoast\\wp\\local\\tools\\import_export_admin' => 'Yoast\\WP\\Local\\Tools\\Import_Export_Admin',
            'yoast\\wp\\seo\\helpers\\capability_helper' => 'Yoast\\WP\\SEO\\Helpers\\Capability_Helper',
            'yoast\\wp\\seo\\helpers\\date_helper' => 'Yoast\\WP\\SEO\\Helpers\\Date_Helper',
            'yoast\\wp\\seo\\loader' => 'Yoast\\WP\\SEO\\Loader',
            'yoast\\wp\\seo\\surfaces\\classes_surface' => 'Yoast\\WP\\SEO\\Surfaces\\Classes_Surface',
            'yoast\\wp\\seo\\surfaces\\helpers_surface' => 'Yoast\\WP\\SEO\\Surfaces\\Helpers_Surface',
            'yoast\\wp\\seo\\surfaces\\open_graph_helpers_surface' => 'Yoast\\WP\\SEO\\Surfaces\\Open_Graph_Helpers_Surface',
            'yoast\\wp\\seo\\surfaces\\schema_helpers_surface' => 'Yoast\\WP\\SEO\\Surfaces\\Schema_Helpers_Surface',
            'yoast\\wp\\seo\\surfaces\\twitter_helpers_surface' => 'Yoast\\WP\\SEO\\Surfaces\\Twitter_Helpers_Surface',
            'yoastseo_vendor\\symfony\\component\\dependencyinjection\\containerinterface' => 'YoastSEO_Vendor\\YoastSEO_Vendor\\Symfony\\Component\\DependencyInjection\\ContainerInterface',
        ];
        $this->methodMap = [
            'Yoast\\WP\\Local\\Builders\\Locations_Repository_Builder' => 'getLocationsRepositoryBuilderService',
            'Yoast\\WP\\Local\\Conditionals\\Admin_Conditional' => 'getAdminConditionalService',
            'Yoast\\WP\\Local\\Conditionals\\Multiple_Locations_Conditional' => 'getMultipleLocationsConditionalService',
            'Yoast\\WP\\Local\\Formatters\\Address_Formatter' => 'getAddressFormatterService',
            'Yoast\\WP\\Local\\Integrations\\Front_End_Integration' => 'getFrontEndIntegrationService',
            'Yoast\\WP\\Local\\Integrations\\Local_Pickup_Notification' => 'getLocalPickupNotificationService',
            'Yoast\\WP\\Local\\Integrations\\Multiple_Locations_Watcher' => 'getMultipleLocationsWatcherService',
            'Yoast\\WP\\Local\\Integrations\\TranslationsPress' => 'getTranslationsPressService',
            'Yoast\\WP\\Local\\PostType\\PostType' => 'getPostTypeService',
            'Yoast\\WP\\Local\\Repositories\\Api_Keys_Repository' => 'getApiKeysRepositoryService',
            'Yoast\\WP\\Local\\Repositories\\Business_Types_Repository' => 'getBusinessTypesRepositoryService',
            'Yoast\\WP\\Local\\Repositories\\Locations_Repository' => 'getLocationsRepositoryService',
            'Yoast\\WP\\Local\\Repositories\\Options_Repository' => 'getOptionsRepositoryService',
            'Yoast\\WP\\Local\\Repositories\\Timezone_Repository' => 'getTimezoneRepositoryService',
            'Yoast\\WP\\Local\\Tools\\Export' => 'getExportService',
            'Yoast\\WP\\Local\\Tools\\Import' => 'getImportService',
            'Yoast\\WP\\Local\\Tools\\Import_Export_Admin' => 'getImportExportAdminService',
            'Yoast\\WP\\SEO\\Helpers\\Capability_Helper' => 'getCapabilityHelperService',
            'Yoast\\WP\\SEO\\Helpers\\Date_Helper' => 'getDateHelperService',
            'Yoast\\WP\\SEO\\Loader' => 'getLoaderService',
            'Yoast\\WP\\SEO\\Surfaces\\Classes_Surface' => 'getClassesSurfaceService',
            'Yoast\\WP\\SEO\\Surfaces\\Helpers_Surface' => 'getHelpersSurfaceService',
            'Yoast\\WP\\SEO\\Surfaces\\Open_Graph_Helpers_Surface' => 'getOpenGraphHelpersSurfaceService',
            'Yoast\\WP\\SEO\\Surfaces\\Schema_Helpers_Surface' => 'getSchemaHelpersSurfaceService',
            'Yoast\\WP\\SEO\\Surfaces\\Twitter_Helpers_Surface' => 'getTwitterHelpersSurfaceService',
            'wp_query' => 'getWpQueryService',
            'wpdb' => 'getWpdbService',
        ];
        $this->privates = [
            'YoastSEO_Vendor\\YoastSEO_Vendor\\Symfony\\Component\\DependencyInjection\\ContainerInterface' => true,
            'wp_query' => true,
            'wpdb' => true,
        ];
        $this->aliases = [
            'YoastSEO_Vendor\\YoastSEO_Vendor\\Symfony\\Component\\DependencyInjection\\ContainerInterface' => 'service_container',
        ];
    }

    public function getRemovedIds()
    {
        return [
            'Psr\\Container\\ContainerInterface' => true,
            'YoastSEO_Vendor\\Symfony\\Component\\DependencyInjection\\ContainerInterface' => true,
            'YoastSEO_Vendor\\YoastSEO_Vendor\\Symfony\\Component\\DependencyInjection\\ContainerInterface' => true,
            'wp_query' => true,
            'wpdb' => true,
        ];
    }

    public function compile()
    {
        throw new LogicException('You cannot compile a dumped container that was already compiled.');
    }

    public function isCompiled()
    {
        return true;
    }

    public function isFrozen()
    {
        @trigger_error(sprintf('The %s() method is deprecated since Symfony 3.3 and will be removed in 4.0. Use the isCompiled() method instead.', __METHOD__), E_USER_DEPRECATED);

        return true;
    }

    /**
     * Gets the public 'Yoast\WP\Local\Builders\Locations_Repository_Builder' shared autowired service.
     *
     * @return \Yoast\WP\Local\Builders\Locations_Repository_Builder
     */
    protected function getLocationsRepositoryBuilderService()
    {
        return $this->services['Yoast\\WP\\Local\\Builders\\Locations_Repository_Builder'] = new \Yoast\WP\Local\Builders\Locations_Repository_Builder();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Conditionals\Admin_Conditional' shared autowired service.
     *
     * @return \Yoast\WP\Local\Conditionals\Admin_Conditional
     */
    protected function getAdminConditionalService()
    {
        return $this->services['Yoast\\WP\\Local\\Conditionals\\Admin_Conditional'] = new \Yoast\WP\Local\Conditionals\Admin_Conditional();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Conditionals\Multiple_Locations_Conditional' shared autowired service.
     *
     * @return \Yoast\WP\Local\Conditionals\Multiple_Locations_Conditional
     */
    protected function getMultipleLocationsConditionalService()
    {
        return $this->services['Yoast\\WP\\Local\\Conditionals\\Multiple_Locations_Conditional'] = new \Yoast\WP\Local\Conditionals\Multiple_Locations_Conditional();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Formatters\Address_Formatter' shared autowired service.
     *
     * @return \Yoast\WP\Local\Formatters\Address_Formatter
     */
    protected function getAddressFormatterService()
    {
        return $this->services['Yoast\\WP\\Local\\Formatters\\Address_Formatter'] = new \Yoast\WP\Local\Formatters\Address_Formatter();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Integrations\Front_End_Integration' shared autowired service.
     *
     * @return \Yoast\WP\Local\Integrations\Front_End_Integration
     */
    protected function getFrontEndIntegrationService()
    {
        return $this->services['Yoast\\WP\\Local\\Integrations\\Front_End_Integration'] = new \Yoast\WP\Local\Integrations\Front_End_Integration(${($_ = isset($this->services['Yoast\\WP\\Local\\Repositories\\Locations_Repository']) ? $this->services['Yoast\\WP\\Local\\Repositories\\Locations_Repository'] : $this->getLocationsRepositoryService()) && false ?: '_'}, ${($_ = isset($this->services['Yoast\\WP\\Local\\PostType\\PostType']) ? $this->services['Yoast\\WP\\Local\\PostType\\PostType'] : ($this->services['Yoast\\WP\\Local\\PostType\\PostType'] = new \Yoast\WP\Local\PostType\PostType())) && false ?: '_'});
    }

    /**
     * Gets the public 'Yoast\WP\Local\Integrations\Local_Pickup_Notification' shared autowired service.
     *
     * @return \Yoast\WP\Local\Integrations\Local_Pickup_Notification
     */
    protected function getLocalPickupNotificationService()
    {
        return $this->services['Yoast\\WP\\Local\\Integrations\\Local_Pickup_Notification'] = new \Yoast\WP\Local\Integrations\Local_Pickup_Notification(${($_ = isset($this->services['Yoast\\WP\\SEO\\Helpers\\Capability_Helper']) ? $this->services['Yoast\\WP\\SEO\\Helpers\\Capability_Helper'] : ($this->services['Yoast\\WP\\SEO\\Helpers\\Capability_Helper'] = new \Yoast\WP\SEO\Helpers\Capability_Helper())) && false ?: '_'});
    }

    /**
     * Gets the public 'Yoast\WP\Local\Integrations\Multiple_Locations_Watcher' shared autowired service.
     *
     * @return \Yoast\WP\Local\Integrations\Multiple_Locations_Watcher
     */
    protected function getMultipleLocationsWatcherService()
    {
        return $this->services['Yoast\\WP\\Local\\Integrations\\Multiple_Locations_Watcher'] = new \Yoast\WP\Local\Integrations\Multiple_Locations_Watcher();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Integrations\TranslationsPress' shared autowired service.
     *
     * @return \Yoast\WP\Local\Integrations\TranslationsPress
     */
    protected function getTranslationsPressService()
    {
        return $this->services['Yoast\\WP\\Local\\Integrations\\TranslationsPress'] = new \Yoast\WP\Local\Integrations\TranslationsPress(${($_ = isset($this->services['Yoast\\WP\\SEO\\Helpers\\Date_Helper']) ? $this->services['Yoast\\WP\\SEO\\Helpers\\Date_Helper'] : ($this->services['Yoast\\WP\\SEO\\Helpers\\Date_Helper'] = new \Yoast\WP\SEO\Helpers\Date_Helper())) && false ?: '_'});
    }

    /**
     * Gets the public 'Yoast\WP\Local\PostType\PostType' shared autowired service.
     *
     * @return \Yoast\WP\Local\PostType\PostType
     */
    protected function getPostTypeService()
    {
        return $this->services['Yoast\\WP\\Local\\PostType\\PostType'] = new \Yoast\WP\Local\PostType\PostType();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Repositories\Api_Keys_Repository' shared autowired service.
     *
     * @return \Yoast\WP\Local\Repositories\Api_Keys_Repository
     */
    protected function getApiKeysRepositoryService()
    {
        return $this->services['Yoast\\WP\\Local\\Repositories\\Api_Keys_Repository'] = new \Yoast\WP\Local\Repositories\Api_Keys_Repository();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Repositories\Business_Types_Repository' shared autowired service.
     *
     * @return \Yoast\WP\Local\Repositories\Business_Types_Repository
     */
    protected function getBusinessTypesRepositoryService()
    {
        return $this->services['Yoast\\WP\\Local\\Repositories\\Business_Types_Repository'] = new \Yoast\WP\Local\Repositories\Business_Types_Repository();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Repositories\Locations_Repository' shared autowired service.
     *
     * @return \Yoast\WP\Local\Repositories\Locations_Repository
     */
    protected function getLocationsRepositoryService()
    {
        return $this->services['Yoast\\WP\\Local\\Repositories\\Locations_Repository'] = new \Yoast\WP\Local\Repositories\Locations_Repository(${($_ = isset($this->services['Yoast\\WP\\Local\\PostType\\PostType']) ? $this->services['Yoast\\WP\\Local\\PostType\\PostType'] : ($this->services['Yoast\\WP\\Local\\PostType\\PostType'] = new \Yoast\WP\Local\PostType\PostType())) && false ?: '_'}, ${($_ = isset($this->services['Yoast\\WP\\Local\\Repositories\\Options_Repository']) ? $this->services['Yoast\\WP\\Local\\Repositories\\Options_Repository'] : ($this->services['Yoast\\WP\\Local\\Repositories\\Options_Repository'] = new \Yoast\WP\Local\Repositories\Options_Repository())) && false ?: '_'});
    }

    /**
     * Gets the public 'Yoast\WP\Local\Repositories\Options_Repository' shared autowired service.
     *
     * @return \Yoast\WP\Local\Repositories\Options_Repository
     */
    protected function getOptionsRepositoryService()
    {
        return $this->services['Yoast\\WP\\Local\\Repositories\\Options_Repository'] = new \Yoast\WP\Local\Repositories\Options_Repository();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Repositories\Timezone_Repository' shared autowired service.
     *
     * @return \Yoast\WP\Local\Repositories\Timezone_Repository
     */
    protected function getTimezoneRepositoryService()
    {
        return $this->services['Yoast\\WP\\Local\\Repositories\\Timezone_Repository'] = new \Yoast\WP\Local\Repositories\Timezone_Repository();
    }

    /**
     * Gets the public 'Yoast\WP\Local\Tools\Export' shared autowired service.
     *
     * @return \Yoast\WP\Local\Tools\Export
     */
    protected function getExportService()
    {
        return $this->services['Yoast\\WP\\Local\\Tools\\Export'] = new \Yoast\WP\Local\Tools\Export(${($_ = isset($this->services['Yoast\\WP\\Local\\Repositories\\Locations_Repository']) ? $this->services['Yoast\\WP\\Local\\Repositories\\Locations_Repository'] : $this->getLocationsRepositoryService()) && false ?: '_'});
    }

    /**
     * Gets the public 'Yoast\WP\Local\Tools\Import' shared autowired service.
     *
     * @return \Yoast\WP\Local\Tools\Import
     */
    protected function getImportService()
    {
        return $this->services['Yoast\\WP\\Local\\Tools\\Import'] = new \Yoast\WP\Local\Tools\Import(${($_ = isset($this->services['Yoast\\WP\\Local\\Repositories\\Business_Types_Repository']) ? $this->services['Yoast\\WP\\Local\\Repositories\\Business_Types_Repository'] : ($this->services['Yoast\\WP\\Local\\Repositories\\Business_Types_Repository'] = new \Yoast\WP\Local\Repositories\Business_Types_Repository())) && false ?: '_'}, ${($_ = isset($this->services['Yoast\\WP\\Local\\PostType\\PostType']) ? $this->services['Yoast\\WP\\Local\\PostType\\PostType'] : ($this->services['Yoast\\WP\\Local\\PostType\\PostType'] = new \Yoast\WP\Local\PostType\PostType())) && false ?: '_'});
    }

    /**
     * Gets the public 'Yoast\WP\Local\Tools\Import_Export_Admin' shared autowired service.
     *
     * @return \Yoast\WP\Local\Tools\Import_Export_Admin
     */
    protected function getImportExportAdminService()
    {
        return $this->services['Yoast\\WP\\Local\\Tools\\Import_Export_Admin'] = new \Yoast\WP\Local\Tools\Import_Export_Admin();
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Helpers\Capability_Helper' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Helpers\Capability_Helper
     */
    protected function getCapabilityHelperService()
    {
        return $this->services['Yoast\\WP\\SEO\\Helpers\\Capability_Helper'] = new \Yoast\WP\SEO\Helpers\Capability_Helper();
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Helpers\Date_Helper' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Helpers\Date_Helper
     */
    protected function getDateHelperService()
    {
        return $this->services['Yoast\\WP\\SEO\\Helpers\\Date_Helper'] = new \Yoast\WP\SEO\Helpers\Date_Helper();
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Loader' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Loader
     */
    protected function getLoaderService()
    {
        $this->services['Yoast\\WP\\SEO\\Loader'] = $instance = new \Yoast\WP\SEO\Loader($this);

        $instance->register_integration('Yoast\\WP\\Local\\Integrations\\Front_End_Integration');
        $instance->register_integration('Yoast\\WP\\Local\\Integrations\\Local_Pickup_Notification');
        $instance->register_integration('Yoast\\WP\\Local\\Integrations\\Multiple_Locations_Watcher');
        $instance->register_integration('Yoast\\WP\\Local\\Integrations\\TranslationsPress');
        $instance->register_initializer('Yoast\\WP\\Local\\PostType\\PostType');
        $instance->register_initializer('Yoast\\WP\\Local\\Repositories\\Api_Keys_Repository');
        $instance->register_initializer('Yoast\\WP\\Local\\Repositories\\Locations_Repository');
        $instance->register_initializer('Yoast\\WP\\Local\\Repositories\\Options_Repository');
        $instance->register_initializer('Yoast\\WP\\Local\\Repositories\\Timezone_Repository');
        $instance->register_initializer('Yoast\\WP\\Local\\Tools\\Export');
        $instance->register_integration('Yoast\\WP\\Local\\Tools\\Export');
        $instance->register_integration('Yoast\\WP\\Local\\Tools\\Import_Export_Admin');
        $instance->register_initializer('Yoast\\WP\\Local\\Tools\\Import');
        $instance->register_integration('Yoast\\WP\\Local\\Tools\\Import');

        return $instance;
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Surfaces\Classes_Surface' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Surfaces\Classes_Surface
     */
    protected function getClassesSurfaceService()
    {
        return $this->services['Yoast\\WP\\SEO\\Surfaces\\Classes_Surface'] = new \Yoast\WP\SEO\Surfaces\Classes_Surface($this);
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Surfaces\Helpers_Surface' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Surfaces\Helpers_Surface
     */
    protected function getHelpersSurfaceService()
    {
        return $this->services['Yoast\\WP\\SEO\\Surfaces\\Helpers_Surface'] = new \Yoast\WP\SEO\Surfaces\Helpers_Surface($this, ${($_ = isset($this->services['Yoast\\WP\\SEO\\Surfaces\\Open_Graph_Helpers_Surface']) ? $this->services['Yoast\\WP\\SEO\\Surfaces\\Open_Graph_Helpers_Surface'] : ($this->services['Yoast\\WP\\SEO\\Surfaces\\Open_Graph_Helpers_Surface'] = new \Yoast\WP\SEO\Surfaces\Open_Graph_Helpers_Surface($this))) && false ?: '_'}, ${($_ = isset($this->services['Yoast\\WP\\SEO\\Surfaces\\Schema_Helpers_Surface']) ? $this->services['Yoast\\WP\\SEO\\Surfaces\\Schema_Helpers_Surface'] : ($this->services['Yoast\\WP\\SEO\\Surfaces\\Schema_Helpers_Surface'] = new \Yoast\WP\SEO\Surfaces\Schema_Helpers_Surface($this))) && false ?: '_'}, ${($_ = isset($this->services['Yoast\\WP\\SEO\\Surfaces\\Twitter_Helpers_Surface']) ? $this->services['Yoast\\WP\\SEO\\Surfaces\\Twitter_Helpers_Surface'] : ($this->services['Yoast\\WP\\SEO\\Surfaces\\Twitter_Helpers_Surface'] = new \Yoast\WP\SEO\Surfaces\Twitter_Helpers_Surface($this))) && false ?: '_'});
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Surfaces\Open_Graph_Helpers_Surface' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Surfaces\Open_Graph_Helpers_Surface
     */
    protected function getOpenGraphHelpersSurfaceService()
    {
        return $this->services['Yoast\\WP\\SEO\\Surfaces\\Open_Graph_Helpers_Surface'] = new \Yoast\WP\SEO\Surfaces\Open_Graph_Helpers_Surface($this);
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Surfaces\Schema_Helpers_Surface' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Surfaces\Schema_Helpers_Surface
     */
    protected function getSchemaHelpersSurfaceService()
    {
        return $this->services['Yoast\\WP\\SEO\\Surfaces\\Schema_Helpers_Surface'] = new \Yoast\WP\SEO\Surfaces\Schema_Helpers_Surface($this);
    }

    /**
     * Gets the public 'Yoast\WP\SEO\Surfaces\Twitter_Helpers_Surface' shared autowired service.
     *
     * @return \Yoast\WP\SEO\Surfaces\Twitter_Helpers_Surface
     */
    protected function getTwitterHelpersSurfaceService()
    {
        return $this->services['Yoast\\WP\\SEO\\Surfaces\\Twitter_Helpers_Surface'] = new \Yoast\WP\SEO\Surfaces\Twitter_Helpers_Surface($this);
    }

    /**
     * Gets the private 'wp_query' shared service.
     *
     * @return \WP_Query
     */
    protected function getWpQueryService()
    {
        return $this->services['wp_query'] = \Yoast\WP\Local\WordPress\Wrapper::get_wp_query();
    }

    /**
     * Gets the private 'wpdb' shared service.
     *
     * @return \wpdb
     */
    protected function getWpdbService()
    {
        return $this->services['wpdb'] = \Yoast\WP\Local\WordPress\Wrapper::get_wpdb();
    }
}
