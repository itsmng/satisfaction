<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginSatisfactionSurveyQuestion
 */
class PluginSatisfactionSurveyQuestion extends CommonDBChild {

   static $rightname = "plugin_satisfaction";
   public $dohistory = true;

   // From CommonDBChild
   public static $itemtype = 'PluginSatisfactionSurvey';
   public static $items_id = 'plugin_satisfaction_surveys_id';

   CONST YESNO                  = 'yesno';
   CONST TEXTAREA               = 'textarea';
   CONST NOTE                   = 'note';
   CONST NUMERIC_SCALE_WITH_NC  = 'numeric_scale_with_nc';

   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return _n('Question', 'Questions', $nb, 'satisfaction');
   }

   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @since version 0.83
    *
    * @param CommonDBTM|CommonGLPI $item CommonDBTM object for which the tab need to be displayed
    * @param bool|int              $withtemplate boolean  is a template object ? (default 0)
    *
    * @return string tab name
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      // can exists for template
      if ($item->getType() == 'PluginSatisfactionSurvey') {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $dbu = new DbUtils();
            $table = $dbu->getTableForItemType(__CLASS__);
            return self::createTabEntry(self::getTypeName(),
                                        $dbu->countElementsInTable($table,
                                                                   [self::$items_id => $item->getID()]));
         }
         return self::getTypeName();
      }
      return '';
   }

   /**
    * show Tab content
    *
    * @since version 0.83
    *
    * @param          $item                  CommonGLPI object for which the tab need to be displayed
    * @param          $tabnum       integer  tab number (default 1)
    * @param bool|int $withtemplate boolean  is a template object ? (default 0)
    *
    * @return true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'PluginSatisfactionSurvey') {
         self::showForSurvey($item, $withtemplate);
      }
      return true;
   }


   /**
    * Question display
    *
    * @param \PluginSatisfactionSurvey $survey
    * @param string                    $withtemplate
    *
    * @return bool
    */
   public static function showForSurvey(PluginSatisfactionSurvey $survey, $withtemplate = '') {
      global $CFG_GLPI;

      $squestions_obj = new self();
      $sID            = $survey->fields['id'];
      $rand_survey    = mt_rand();

      $canadd   = Session::haveRight(self::$rightname, CREATE);
      $canedit  = Session::haveRight(self::$rightname, UPDATE);
      $canpurge = Session::haveRight(self::$rightname, PURGE);

      //check if answer exists to forbid edition
      $answer       = new PluginSatisfactionSurveyAnswer;
      $found_answer = $answer->find([self::$items_id => $survey->fields['id']]);
      if (count($found_answer) > 0) {
         echo "<span style='font-weight:bold; color:red'>" . __('You cannot edit the questions when answers exists for this survey. Disable this survey and create a new one !', 'satisfaction') . "</span>";
         $canedit  = false;
         $canadd   = false;
         $canpurge = false;
      }

      echo "<div id='viewquestion" . $sID . "$rand_survey'></div>\n";
      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddQuestion$sID$rand_survey() {\n";
         $params = ['type'          => __CLASS__,
                         'parenttype'    => 'PluginSatisfactionSurvey',
                         self::$items_id => $sID,
                         'id'            => -1];
         Ajax::updateItemJsCode("viewquestion$sID$rand_survey",
                                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center'>" .
              "<a href='javascript:viewAddQuestion$sID$rand_survey();'>";
         echo __('Add a question', 'satisfaction') . "</a></div><br>\n";

      }

      // Display existing questions
      $questions = $squestions_obj->find([self::$items_id => $sID], 'id');
      if (count($questions) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . __('No questions for this survey', 'satisfaction') . "</th>";
         echo "</tr></table>";
      } else {

         $rand = mt_rand();
         if ($canpurge) {
            $massiveactionparams = [
                'item' => __CLASS__,
                'container' => 'mass' . __CLASS__ . $rand,
                'display_arrow' => false,
            ];
            Html::showMassiveActions($massiveactionparams);
         }
         $fields = [
            'name'          => self::getTypeName(2),
            'type'          => __('Type'),
            'required'   => __('required', 'satisfaction')
         ];
         $values = [];
         $massive_action = [];

         foreach ($questions as $question) {
            if ($squestions_obj->getFromDB($question['id'])) {
                $values[$question['id']] = [
                    'name' => $squestions_obj->fields['name'],
                    'type' => self::getQuestionType($squestions_obj->fields['type']),
                    'required' => $squestions_obj->fields['is_required'] ? __('required', 'satisfaction') : ''
                ];
                $massive_action[$question['id']] = sprintf('item[%s][%s]', __CLASS__, $question['id']);
            }
         }

         renderTwigTemplate('table.twig', [
            'id' => 'mass' . __CLASS__ . $rand,
            'fields' => $fields,
            'values' => $values,
            'massive_action' => $massive_action,
         ]);

         if ($canpurge) {
            $paramsma['ontop'] = false;
            Html::showMassiveActions($paramsma);
            Html::closeForm();
         }
      }
   }

   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (isset($options['parent']) && !empty($options['parent'])) {
         $survey = $options['parent'];
      }

      $surveyquestion = new self();
      if ($ID <= 0) {
         $surveyquestion->getEmpty();
      } else {
         $surveyquestion->getFromDB($ID);
      }

      if (!$surveyquestion->canView()) {
         return false;
      }

      $NOTE = self::NOTE;
      $NUMERIC_SCALE_WITH_NC = self::NUMERIC_SCALE_WITH_NC;
      $script = <<<JAVASCRIPT
         function plugin_satisfaction_loadtype(val, note){
           if(val == "$NOTE") {
             $('#show_note').show();
             $('#show_note input').prop('disabled', '');
             $('#show_numeric_scale_with_nc').hide();
             $('#show_show_numeric_scale_with_nc input').prop('disabled', 'disabled');
           } else if(val == "$NUMERIC_SCALE_WITH_NC") {
             $('#show_numeric_scale_with_nc').show();
             $('#show_numeric_scale_with_nc input').prop('disabled', '');
             $('#show_note').hide();
             $('#show_note input').prop('disabled', 'disabled');
           } else {
             $('#show_numeric_scale_with_nc').hide();
             $('#show_numeric_scale_with_nc input').prop('disabled', 'disabled');
             $('#show_note').hide();
             $('#show_note input').prop('disabled', 'disabled');
           }
         };
      JAVASCRIPT;

      echo Html::scriptBlock($script);

      $form = [
         'action' => $surveyquestion->getFormURL(),
         'buttons' => [
             [
                 'name' => $ID <= 0 ? 'add' : 'update',
                 'value' => $ID <= 0 ? _sx('button', 'Add') : _sx('button', 'Save'),
                 'class' => 'btn btn-secondary'
             ],
         ],
         'content' => [
             __('Add a question', 'satisfaction') => [
                 'visible' => true,
                 'inputs' => [
                     [
                         'type' => 'hidden',
                         'name' => self::$items_id,
                         'value' => $surveyquestion->fields[self::$items_id],
                     ],
                     $ID <= 0 ? [
                         'type' => 'hidden',
                         'name' => self::$items_id,
                         'value' => $survey->getField('id'),
                     ] : [],
                     $ID > 0 ? [
                         'type' => 'hidden',
                         'name' => 'id',
                         'value' => $ID,
                     ] : [],
                     self::getTypeName(1) => [
                         'name' => 'name',
                         'type' => 'textarea',
                         'value' => $surveyquestion->fields["name"],
                         'col_lg' => 6,
                     ],
                     __('Comments') => [
                         'name' => 'comment',
                         'type' => 'textarea',
                         'value' => $surveyquestion->fields["comment"],
                         'col_lg' => 6,
                     ],
                     __('Type') => [
                         'name' => 'type',
                         'type' => 'select',
                         'values' => self::getQuestionTypeList(),
                         'value' => $surveyquestion->fields['type'],
                         'hooks' => [
                            'change' => "plugin_satisfaction_loadtype(this.value);",
                         ],
                         'col_lg' => 8,
                     ],
                     __('required', 'satisfaction') => [
                         'name' => 'is_required',
                         'type' => 'checkbox',
                         'value' => $surveyquestion->fields['is_required'] ? 1 : 0,
                     ],
                 ],
            ],
            [
                'attributes' => [
                    'id' => 'show_numeric_scale_with_nc',
                    'style' => $surveyquestion->fields['type'] == self::NUMERIC_SCALE_WITH_NC ? '' : 'display: none;',
                ],
                'inputs' => [
                    __('Note minimum', 'satisfaction') => [
                        'name' => 'minimun',
                        'type' => 'number',
                        'value' => $surveyquestion->fields['minimun']?:0,
                        'col_lg' => 6,
                        $surveyquestion->fields['type'] != self::NUMERIC_SCALE_WITH_NC ? 'disabled' : '' => '',
                    ],
                    __('Note maximum', 'satisfaction') => [
                        'name' => 'maximun',
                        'type' => 'number',
                        'value' => $surveyquestion->fields['maximun']?:10,
                        'col_lg' => 6,
                        $surveyquestion->fields['type'] != self::NUMERIC_SCALE_WITH_NC ? 'disabled' : '' => '',
                    ],
                ],
            ],
            [
                'attributes' => [
                    'id' => 'show_note',
                    'style' => $surveyquestion->fields['type'] == self::NOTE ? '' : 'display: none;',
                ],
                'inputs' => [
                    __('Note on', 'satisfaction') => [
                        'name' => 'maximun',
                        'type' => 'number',
                        'value' => $surveyquestion->fields['maximun']?:10,
                        'min' => 1,
                        'col_lg' => 6,
                        $surveyquestion->fields['type'] != self::NOTE ? 'disabled' : '' => '',
                    ],
                    __('Default value') => [
                        'name' => 'default_value',
                        'type' => 'number',
                        'value' => $surveyquestion->fields['default_value'],
                        'min' => 1,
                        'max' => $surveyquestion->fields['maximun'],
                        'col_lg' => 6,
                        $surveyquestion->fields['type'] != self::NOTE ? 'disabled' : '' => '',
                    ],
                ],
            ]
         ],
      ];
      renderTwigForm($form);
   }

   /**
    * Display line with name & type
    *
    * @param $canedit
    * @param $rand
    */
   function showOne($canedit, $canpurge, $rand) {
      global $CFG_GLPI;

      $style = '';
      if ($canedit) {
         $style = "style='cursor:pointer' onClick=\"viewEditQuestion" .
                  $this->fields[self::$items_id] .
                  $this->fields['id'] . "$rand();\"" .
                  " id='viewquestion" . $this->fields[self::$items_id] . $this->fields["id"] . "$rand'";
      }
      echo "<tr class='tab_bg_2' $style>";

      if ($canpurge) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox(__CLASS__, $this->fields["id"]);
         echo "</td>";
      }

      if ($canedit) {
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditQuestion" . $this->fields[self::$items_id] . $this->fields["id"] . "$rand() {\n";
         $params = ['type'          => __CLASS__,
                    'parenttype'    => self::$itemtype,
                    self::$items_id => $this->fields[self::$items_id],
                    'id'            => $this->fields["id"]];
         Ajax::updateItemJsCode("viewquestion" . $this->fields[self::$items_id] . "$rand",
                                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
      }

      $name = $this->fields["name"];

      echo "<td class='left'>" . nl2br($name) . "</td>";
      echo "<td class='left'>" . self::getQuestionType($this->fields["type"]) . "</td>";
      echo "<td class='left'>" . ($this->fields["is_required"] ? __('is required', 'satisfaction') : '') . "</td>";
      echo "</tr>";
   }

   /**
    * List of question types
    *
    * @return array
    */
   static function getQuestionTypeList() {
      $array                                  = [];
      $array[self::YESNO]                     = __('Yes') . '/' . __('No');
      $array[self::TEXTAREA]                  = __('Text', 'satisfaction');
      $array[self::NOTE]                      = __('Note', 'satisfaction');
      $array[self::NUMERIC_SCALE_WITH_NC]     = __('Numeric scale', 'satisfaction');
      return $array;
   }

   /**
    * Return the type
    *
    * @return array
    */
   static function getQuestionType($type) {
      return self::getQuestionTypeList()[$type];
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

}
