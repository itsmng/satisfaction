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
            //TODO : Detect delete to update history
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['item' => __CLASS__, 'container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr>";
         if ($canpurge) {
            echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
         }
         echo "<th>" . self::getTypeName(2) . "</th>";
         echo "<th>" . __('Type') . "</th>";
         echo "<th>" . __('required', 'satisfaction') . "</th></tr>";

         foreach ($questions as $question) {
            if ($squestions_obj->getFromDB($question['id'])) {
               $squestions_obj->showOne($canedit, $canpurge, $rand_survey);
            }
         }
         echo "</table>";

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

      echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL(self::getType()) . "'>";

      echo "<div align='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('Add a question', 'satisfaction') . "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . self::getTypeName(1) . "&nbsp;:</td>";
      echo "<td><textarea name='name' cols='50' rows='4'>" .
           $surveyquestion->fields["name"] . "</textarea></td>";
      echo "<input type='hidden' name='" . self::$items_id . "' value='" .
           $surveyquestion->fields[self::$items_id] . "'>";
      echo "</td>";
      echo "<td rowspan='2'>" . __('Comments') . "</td>";
      echo "<td rowspan='2'>";
      echo "<textarea cols='60' rows='6' name='comment' >" . $surveyquestion->fields["comment"] . "</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Type') . "</td>";
      echo "<td>";
      $array = self::getQuestionTypeList();
      Dropdown::showFromArray('type', $array, ['value'     => $surveyquestion->fields['type'],
                                               'on_change' => "plugin_satisfaction_loadtype(this.value);"]);

      $NOTE = self::NOTE;
      $NUMERIC_SCALE_WITH_NC = self::NUMERIC_SCALE_WITH_NC;
      $script = <<<JAVASCRIPT
function plugin_satisfaction_loadtype(val, note){
  if(val == "$NOTE") {
    $('#show_note').show();
    $('#show_note select').prop('disabled', '');
    $('#show_numeric_scale_with_nc').hide();
    $('#show_show_numeric_scale_with_nc select').prop('disabled', 'disabled');
  } else if(val == "$NUMERIC_SCALE_WITH_NC") {
    $('#show_numeric_scale_with_nc').show();
    $('#show_numeric_scale_with_nc select').prop('disabled', '');
    $('#show_note').hide();
    $('#show_note select').prop('disabled', 'disabled');
  } else {
    $('#show_numeric_scale_with_nc').hide();
    $('#show_numeric_scale_with_nc select').prop('disabled', 'disabled');
    $('#show_note').hide();
    $('#show_note select').prop('disabled', 'disabled');
  }
};
JAVASCRIPT;

      echo Html::scriptBlock($script);
      $isNote = $surveyquestion->fields['type'] == self::NOTE;
      $isNumeric = $surveyquestion->fields['type'] == self::NUMERIC_SCALE_WITH_NC;
      $NoteStyle = $isNote ? "" : "style='display: none '";
      $NumericStyle = $isNumeric ? "" : "style='display: none '";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' id='show_numeric_scale_with_nc' $NumericStyle>";
      echo "<td>";
      echo __('Note minimun', 'satisfaction');
      echo "</td>";
      echo "<td>";
      Dropdown::showNumber('minimun', ['max'   => 1,
                                      'min'   => 0,
                                      'value' => $surveyquestion->fields['minimun']?:0,
                                      'specific_tags' => $isNumeric ? [] : ['disabled' => 'disabled']]);
      echo "</td>";
      echo "<td>";
      echo __('Note maximun', 'satisfaction');
      echo "</td>";
      echo "<td>";
      Dropdown::showNumber('maximun', ['max'   => 10,
                                      'min'   => 2,
                                      'value' => $surveyquestion->fields['maximun']?:10,
                                      'specific_tags' => $isNumeric ? [] : ['disabled' => 'disabled']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' id='show_note' $NoteStyle>";
      echo "<td>";
      echo __('Note on', 'satisfaction');
      echo "</td>";
      echo "<td>";
      Dropdown::showNumber('maximun', ['max'   => 10,
                                      'min'   => 2,
                                      'value' => $surveyquestion->fields['maximun']?:10,
                                      'specific_tags' => $isNote ? [] : ['disabled' => 'disabled'],
                                      'on_change' => "plugin_satisfaction_load_defaultvalue(\"" . Plugin::getWebDir('satisfaction') . "\", this.value);"]);
      echo "</td>";

      $max_default_value = $surveyquestion->fields['maximun']?:10;

      echo "<td>";
      echo __('Default value');
      echo "</td>";
      echo "<td id='default_value'>";
      Dropdown::showNumber('default_value', ['max'   => $max_default_value,
                                      'min'   => 1,
                                      'specific_tags' => $isNote ? [] : ['disabled' => 'disabled'],
                                      'value' => $surveyquestion->fields['default_value']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo '<label for="is_required">' . __('required', 'satisfaction') . "</label>";
      echo "</td>";
      echo "<td>";
      echo '<input type="hidden" name="is_required" value="0" />';
      echo '<input type="checkbox" id="is_required" name="is_required"'. ($surveyquestion->fields['is_required'] ? ' checked' : '' ) .' value="1" />';
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='4'>";
      if ($ID <= 0) {
         echo Html::hidden(self::$items_id, ['value' => $survey->getField('id')]);
         echo "<input type='submit' name='add' class='submit' value='" . _sx('button', 'Add') . "' >";
      } else {
         echo Html::hidden('id', ['value' => $ID]);
         echo "<input type='submit' name='update' class='submit' value='" . _sx('button', 'Save') . "' >";
      }
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      Html::closeForm();
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
