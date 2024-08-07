<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

include(dirname(__FILE__)."/surveytranslation.dao.php");

/**
 * PluginSatisfactionSurveyTranslation Class
 **/
class PluginSatisfactionSurveyTranslation extends CommonDBChild {

   static public $itemtype = 'itemtype';
   static public $items_id = 'items_id';
   public $dohistory       = true;
   static $rightname       = 'plugin_satisfaction';

   static function getTypeName($nb = 0) {
      return _n('Translation', 'Translations', $nb);
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (self::canBeTranslated($item)) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::getNumberOfTranslationsForItem($item);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }

   /**
    * Get the standard massive actions which are forbidden
    *
    * @since version 0.84
    *
    * @return an array of massive actions
    **/
   public function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    * Check if an item can be translated
    * It be translated if translation if globally on and item is an instance of CommonDropdown
    * or CommonTreeDropdown and if translation is enabled for this class
    *
    * @param $item the item to check
    *
    * @return true if item can be translated, false otherwise
    **/
   static function canBeTranslated(CommonGLPI $item) {
      return $item instanceof PluginSatisfactionSurvey && $item->maybeTranslated();
   }

   /**
    * Return the number of translations for an item
    *
    * @param item
    *
    * @return the number of translations for this item
    **/
   static function getNumberOfTranslationsForItem($item) {
      return PluginSatisfactionSurveyTranslationDAO::countSurveyTranslationByCrit(["plugin_satisfaction_surveys_id" => $item->getID()]);
   }

   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if (PluginSatisfactionSurveyTranslation::canBeTranslated($item)) {
         PluginSatisfactionSurveyTranslation::showTranslations($item);
      }
      return true;
   }

   /**
    * Display all translated field for a dropdown
    *
    * @param $item a Dropdown item
    *
    * @return true;
    **/
   static function showTranslations(PluginSatisfactionSurvey $item) {
      global $CFG_GLPI;

      // Get all translation from database
      $items = PluginSatisfactionSurveyTranslationDAO::getSurveyTranslationByCrit(["plugin_satisfaction_surveys_id" => $item->getID()]);

      $rand    = mt_rand();
      $canedit = $item->can($item->getID(), UPDATE);
      $target = Plugin::getWebDir('satisfaction')."/ajax/surveytranslation.form.php";

      if ($canedit) {
         echo "<div id='viewtranslation" . $item->getType().$item->getID() . "$rand'></div>\n";

         echo "<script type='text/javascript' >\n";
         echo "function addTranslation" . $item->getType().$item->getID() . "$rand() {\n";
         $params = [
            'id' => -1,
            'survey_id' => $item->getID(),
            'action' => 'GET'
         ];
         Ajax::updateItemJsCode("viewtranslation" . $item->getType().$item->getID() . "$rand",
            $target,
            $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center'>".
            "<a class='btn btn-secondary mt-2' href='javascript:addTranslation".
            $item->getType().$item->getID()."$rand();'>". __('Add a new translation').
            "</a></div><br>";
      }

      if (count($items)) {

         // ** MASS ACTION **
         // TODO Remove edit action
         if ($canedit) {
            $massiveactionparams = [
                'container' => 'mass'.__CLASS__.$rand,
                'display_arrow' => false,
            ];
            Html::showMassiveActions($massiveactionparams);
         }
         // ** MASS ACTION **

         echo "<h2>".__("List of translations")."</h2>";
         $fields = [
            'language' => __('Language'),
            'question' => __('Question'),
            'val' => __('Value'),
            'edit' => __('Edit'),
         ];
         $values = [];
         $massiveactionValues = [];
         foreach ($items as $data) {
            $newValue = [];
            if ($canedit) {
               $massiveactionValues[$data["id"]] = sprintf('item[%s][%s]', PluginSatisfactionSurveyTranslation::getType(), $data["id"]);
            }

            if ($canedit) {
               $url = Plugin::getWebDir('satisfaction')."/ajax/surveytranslation.form.php";
               $dataId = $data["id"];
               $surveyId = $item->getID();
               echo <<<HTML
                  <script type='text/javascript' >
                  function viewEditTranslation{$data['id']}$rand() {
                     $.ajax({
                        method: 'POST',
                        url: '$url',
                        data: {
                           id: $dataId,
                           survey_id: $surveyId,
                           action: 'GET'
                        },
                        success: function(data) {
                           $('#viewtranslation{$item->getType()}{$item->getID()}$rand').html(data);
                        }
                     });
                  }
                  </script>
               HTML;
            }
            $newValue['language'] = Dropdown::getLanguageName($data['language']);

            $surveyQuestion = new PluginSatisfactionSurveyQuestion();
            $surveyQuestion->getFromDB($data['glpi_plugin_satisfaction_surveyquestions_id']);

            $newValue['question'] = $surveyQuestion->getName();
            $newValue['val'] = $data['value'];
            $newValue['edit'] = <<<HTML
                <a class='btn btn-sm btn-secondary' href='javascript:viewEditTranslation{$data['id']}$rand();'>
                    edit
                </a>
            HTML;
            $values[$data["id"]] = $newValue;
         }
         renderTwigTemplate('table.twig', [
            'id' => 'mass'.__CLASS__.$rand,
            'fields' => $fields,
            'values' => $values,
            'massive_action' => $massiveactionValues,
         ]);
      } else {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . __("No translation found", "satisfaction")."</th></tr></table>";
      }
      return true;

   }

   function showForm($options){
      global $CFG_GLPI;
      $surveyId = Toolbox::cleanInteger($options['survey_id']);

      $item = new PluginSatisfactionSurvey();
      $item->getFromDB($surveyId);

      if ($options['id'] > 0) {
         $item->check($surveyId, READ);
      } else {
         // Create item
         $item->check(-1, CREATE);
      }

      $item = new PluginSatisfactionSurveyQuestion();
      $datas = $item->find(['plugin_satisfaction_surveys_id' => $surveyId]);

      $questions = [];
      foreach($datas as $data){
         $questions[$data['id']] = $data['name'];
      }

      if ($options['id'] > 0) {
          $surveyTranslationData = PluginSatisfactionSurveyTranslationDAO::getSurveyTranslationByID($options['id']);
          $surveyQuestion = new PluginSatisfactionSurveyQuestion();
          $surveyQuestion->getFromDB($surveyTranslationData['glpi_plugin_satisfaction_surveyquestions_id']);
      }

      $form = [
         'action' => Plugin::getWebDir('satisfaction')."/ajax/surveytranslation.form.php",
         'buttons' => [
            [
               'name' => 'update',
               'value' => $options['id'] > 0 ? _sx('button', 'Save') : _sx('button', 'Add'),
               'class' => 'btn btn-secondary'
            ],
         ],
         'content' => [
            __('Add a translation', 'satisfaction') => $options['id'] > 0 ? [
                'visible' => true,
                'inputs' => [
                    [
                       'type' => 'hidden',
                       'name' => 'survey_id',
                       'value' => $surveyId,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'id',
                        'value' => $options['id'],
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'action',
                        'value' => 'EDIT',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'language',
                        'value' => $options['language'],
                    ],
                    [
                        'name' => 'question_id',
                        'type' => 'hidden',
                        'value' => $surveyQuestion->getID(),
                    ],
                    __('Language') => [
                        'content' => Dropdown::getLanguageName($surveyTranslationData['language']),
                        'col_lg' => 6,
                    ],
                    __('Question') => [
                        'content' => $surveyQuestion->getName(),
                        'col_lg' => 6,
                    ],
                    __('Value') => [
                        'type' => 'textarea',
                        'name' => 'value',
                        'value' => $surveyTranslationData['value'],
                        'col_lg' => 12,
                        'col_md' => 12,
                    ],
                ],
            ] : [
               'visible' => true,
               'inputs' => [
                  [
                     'type' => 'hidden',
                     'name' => 'survey_id',
                     'value' => $surveyId,
                  ],
                  [
                     'type' => 'hidden',
                     'name' => 'action',
                     'value' => 'NEW',
                  ],
                  $options['id'] > 0 ? [
                     'type' => 'hidden',
                     'name' => 'id',
                     'value' => $options['id'],
                  ] : [],
                  __('Language') => [
                     'name' => 'language',
                     'type' => 'select',
                     'values' => Language::getLanguages(),
                     'value' => $_SESSION['glpilanguage'],
                     'col_lg' => 6,
                  ],
                  __('Question') => [
                     'name' => 'question_id',
                     'type' => 'select',
                     'values' => $questions,
                     'value' => $options['question_id'],
                     'col_lg' => 6,
                  ],
                  __('Value') => [
                     'name' => 'value',
                     'type' => 'textarea',
                     'value' => $options['value'],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
               ],
            ],
         ],
      ];
      renderTwigForm($form);
   }

   function getQuestionDropdown($surveyId){

      $item = new PluginSatisfactionSurveyQuestion();
      $datas = $item->find(['plugin_satisfaction_surveys_id' => $surveyId]);

      $temp = [];
      foreach($datas as $data){
         $temp[$data['id']] = $data['name'];
      }

      $params = [
         "name"=> 'question_id',
         "display"=>false,
         "width"=> '200px',
         'display_emptychoice' => true
      ];

      return Dropdown::showFromArray($params['name'], $temp, $params);
   }

   function getFormHeader($translationID, $surveyID){

      global $CFG_GLPI;
      $target = Plugin::getWebDir('satisfaction')."/ajax/surveytranslation.form.php";

      $result = "<form name='form' method='post' action='$target' enctype='multipart/form-data'>";
      $result.= "<input type='hidden' name='survey_id' value='$surveyID'>";
      $result.= "<div class='spaced' id='tabsbody'>";
      $result.= "<table class='tab_cadre_fixe' id='mainformtable'>";
      $result.= "<tbody>";

      // First Title Line
      $result.= "<tr class='headerRow'><th colspan='3'>";
      $result.= $translationID > 0 ? __("Edit") : __("Add") ;
      $result.= " ".__("Translation");
      $result.= "</th></tr>";

      // Second title line
      $result.= "<tr class='headerRow'>";
      $result.= "<th>".__("Language")."</th>";
      $result.= "<th>".__("Question")."</th>";
      $result.= "<th>".__("Value")."</th></tr>";
      $result.= "</tr>";

      return $result;
   }

   function newSurveyTranslation($options){
      global $CFG_GLPI;
      $crit = [
         'plugin_satisfaction_surveys_id' => $options['survey_id'],
         'glpi_plugin_satisfaction_surveyquestions_id' => $options['question_id'],
         'language' => $options['language']
      ];

      // Translation already exist
      if(PluginSatisfactionSurveyTranslationDAO::countSurveyTranslationByCrit($crit)){
         Session::addMessageAfterRedirect(
            sprintf(__("An %s translation for this Question already exist.", "satisfaction"), $CFG_GLPI['languages'][$options["language"]][0]),
            true,
            WARNING);
      }
      // Translation ready to insert
      else{
         $newInsertId = PluginSatisfactionSurveyTranslationDAO::newSurveyTranslation(
            $options['survey_id'],
            $options['question_id'],
            $options['language'],
            $options['value']
         );
         if($newInsertId != null){
            Session::addMessageAfterRedirect(__("Translation successfully created.", "satisfaction"), true, INFO);

            if ($this->dohistory) {
               $changes = [
                  $newInsertId,
                  '',
                  $options['value']
               ];
               Log::history($options['survey_id'], PluginSatisfactionSurvey::class, $changes, $this->getType(),
                  static::$log_history_add);
            }
         }else{
            Session::addMessageAfterRedirect(__("Translation creation failed", "satisfaction"), true, ERROR);
         }
      }
   }

   function editSurveyTranslation($options){
      global $CFG_GLPI;
      $crit = [
         'id' => $options['id']
      ];

      // Translation doesn't exist
      if(!PluginSatisfactionSurveyTranslationDAO::countSurveyTranslationByCrit($crit)){
         Session::addMessageAfterRedirect(
            __("The translation you want to edit does not exist.", "satisfaction"),
            true,
            WARNING);
      }
      // Translation ready to update
      else{
         $surveyTranslationData = PluginSatisfactionSurveyTranslationDAO::getSurveyTranslationByID($options['id']);

         PluginSatisfactionSurveyTranslationDAO::editSurveyTranslation($options['id'],$options['value']);

         Session::addMessageAfterRedirect(__("Translation successfully edited.", "satisfaction"), true, INFO);

         if ($this->dohistory) {

            $changes = [
               $options['id'],
               $surveyTranslationData['value'],
               $options['value']
            ];
            Log::history($options['survey_id'], PluginSatisfactionSurvey::class, $changes, $this->getType(),
               static::$log_history_update);
         }
      }
   }

   static function hasTranslation($surveyId, $questionId){
      return PluginSatisfactionSurveyTranslationDAO::countSurveyTranslationByCrit([
         'plugin_satisfaction_surveys_id' => $surveyId,
         'glpi_plugin_satisfaction_surveyquestions_id' => $questionId,
         'language' => $_SESSION['glpilanguage']
      ]);
   }

   static function getTranslation($surveyId, $questionId){

      $crit = [
         'plugin_satisfaction_surveys_id' => $surveyId,
         'glpi_plugin_satisfaction_surveyquestions_id' => $questionId,
         'language' => $_SESSION['glpilanguage']
      ];

      $translationList = PluginSatisfactionSurveyTranslationDAO::getSurveyTranslationByCrit($crit);
      $translation = array_pop($translationList);

      return $translation['value'];
   }
}
