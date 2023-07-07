ALTER TABLE glpi_plugin_satisfaction_surveyquestions
  CHANGE COLUMN `number` `maximun` int(11) DEFAULT NULL,
  ADD COLUMN `is_required` tinyint(1) NOT NULL default 0,
  MODIFY COLUMN `default_value` int(11) DEFAULT NULL;

