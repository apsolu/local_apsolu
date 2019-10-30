<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_apsolu\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_chart($options=[]) {
       
      $result = (object)\local_apsolu_webservices::get_chartdataset($options);
     
      if ($result->success) {
        $htmlId = uniqid();
        
        // filtres
        $htmlChartfilter = "";
        if (isset($options['criterias'])) { 
          $htmlChartfilter = $this->render_from_template('local_apsolu/statistics_chart_filters',  (object) [
              'uniqid' => $htmlId,
              'options' => json_encode($options),
          ]);
        }
        
        // Rendu graphique
        $htmlChart = $this->render_from_template('core/chart', (object) [
            'uniqid' => $htmlId,
            'chartdata' => $result->chartdata,
            'withtable' => true
        ]);
         
        return $htmlChartfilter.$htmlChart;
      } else {
        return get_string("statistics_noavailabledata","local_apsolu");
      }

    }
    
    public function render_reportCounter($options=[]) {
      $result = (object)\local_apsolu_webservices::get_reportdataset($options['classname'],$options['reportid']);
  
      if ($result->success) {
        $Enrolments = json_decode($result->data);
        return count($Enrolments); 
      } else {
        return get_string("statistics_noavailabledata","local_apsolu");
      }
    }
    
    
}