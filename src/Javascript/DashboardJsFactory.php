<?php

namespace Khill\Lavacharts\Javascript;

use \Khill\Lavacharts\Dashboards\Dashboard;

/**
 * DashboardFactory Class
 *
 * This class takes Chart and Control Wrappers and uses all of the info to build the complete
 * javascript blocks for outputting into the page.
 *
 * @category   Class
 * @package    Khill\Lavacharts\Javascript
 * @since      3.0.0
 * @author     Kevin Hill <kevinkhill@gmail.com>
 * @copyright  (c) 2016, KHill Designs
 * @link       http://github.com/kevinkhill/lavacharts GitHub Repository Page
 * @link       http://lavacharts.com                   Official Docs Site
 * @license    http://opensource.org/licenses/MIT MIT
 */
class DashboardJsFactory extends JavascriptFactory
{
    /**
     * Location of the output template.
     *
     * @var string
     */
    const OUTPUT_TEMPLATE = 'templates/dashboard.tmpl.js';

    /**
     * Dashboard to generate javascript from.
     *
     * @var \Khill\Lavacharts\Dashboards\Dashboard
     */
    protected $dashboard;

    /**
     * Creates a new DashboardFactory with the javascript template.
     *
     * @param \Khill\Lavacharts\Dashboards\Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;

        parent::__construct(self::OUTPUT_TEMPLATE);
    }

    /**
     * Builds the Javascript code block for a Dashboard
     *
     * @access protected
     * @return string Javascript code block.
     */
    protected function getTemplateVars()
    {
        $boundCharts = $this->dashboard->getBoundCharts();

        $vars = [
            'label'     => $this->dashboard->getLabelStr(),
            'version'   => Dashboard::VERSION,
            'class'     => $this->dashboard->getJsClass(),
            'packages'  => [
                $this->dashboard->getJsPackage()
            ],
            'elemId'    => $this->dashboard->getElementIdStr(),
            'bindings'  => $this->processBindings()
        ];

        /** @var \Khill\Lavacharts\Charts\Chart $chart */
        foreach ($boundCharts as $chart) {
            $vars['chartData'] = $chart->getDataTableJson();

            array_push($vars['packages'], $chart->getJsPackage());
        }

        $vars['packages'] = json_encode(array_unique($vars['packages']));

        return $vars;
    }

    /**
     * Process all the bindings for a Dashboard.
     *
     * Turns the chart and control wrappers into new Google Visualization Objects.
     *
     * @return string
     */
    public function processBindings()
    {
        $buffer = '';
        $bindings = $this->dashboard->getBindings();

        /** @var \Khill\Lavacharts\Dashboards\Bindings\Binding $binding */
        foreach ($bindings as $binding) {
            switch ($binding::TYPE) {
                case 'OneToOne':
                    $controls = $binding->getControlWrappers()[0]->getJsConstructor();
                    $charts   = $binding->getChartWrappers()[0]->getJsConstructor();
                    break;

                case 'OneToMany':
                    $controls = $binding->getControlWrappers()[0]->getJsConstructor();
                    $charts   = $this->mapWrapperArray($binding->getChartWrappers());
                    break;

                case 'ManyToOne':
                    $controls = $this->mapWrapperArray($binding->getControlWrappers());
                    $charts   = $binding->getChartWrappers()[0]->getJsConstructor();
                    break;

                case 'ManyToMany':
                    $controls = $this->mapWrapperArray($binding->getControlWrappers());
                    $charts   = $this->mapWrapperArray($binding->getChartWrappers());
                    break;
            }

            $buffer .= sprintf('this.dashboard.bind(%s, %s);', $controls, $charts);
        }

        return $buffer;
    }

    /**
     * Map the wrapper values from the array to javascript notation.
     *
     * @access protected
     * @param  array $wrapperArray Array of control or chart wrappers
     * @return string Json notation for the wrappers
     */
    protected function mapWrapperArray($wrapperArray)
    {
        $wrappers = array_map(function ($wrapperArray) {
            return $wrapperArray->getJsConstructor();
        }, $wrapperArray);

        return '[' . implode(', ', $wrappers) . ']';
    }
}
