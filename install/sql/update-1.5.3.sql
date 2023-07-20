ALTER TABLE glpi_plugin_satisfaction_surveyquestions
  CHANGE COLUMN `number` `maximun` tinyint(1) DEFAULT NULL,
  ADD COLUMN `minimun` tinyint(1) DEFAULT NULL,
  ADD COLUMN `is_required` tinyint(1) NOT NULL default 0,
  MODIFY COLUMN `default_value` tinyint(1) DEFAULT NULL;

