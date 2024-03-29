<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 resources plugin for ITSM-NG
 Copyright (C) 2009-2016 by the resources Development Team.

 https://github.com/itsmng/resources
 
 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of satisfaction.

 satisfaction is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 satisfaction is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with satisfaction. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */


include('../../../inc/includes.php');

Session::checkLoginUser();

$reminder = new PluginSatisfactionSurveyReminder();

if (isset($_POST["add"])) {

   $input = $_POST;

   if(isset($input[$reminder::PREDEFINED_REMINDER_OPTION_NAME])){
      $input = $reminder->generatePredefinedReminderForAdd($input);
   }

   $reminder->check(-1, CREATE, $input);
   $reminder->add($input);
   Html::back();

} else if (isset($_POST["update"])) {
   $reminder->check($_POST['id'], UPDATE);
   $reminder->update($_POST);
   Html::back();

} else if (isset($_POST["delete"])) {
   $reminder->check($_POST['id'], PURGE);
   $reminder->delete($_POST);
   Html::back();

}

Html::displayErrorAndDie('Lost');
