<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Dbhealth_Form_Report_DBHealth',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Database Health Report',
      'description' => 'Database Health Report: Provides statistics on how extensively a Drupal-based CiviCRM database is being used',
      'class_name' => 'CRM_Dbhealth_Form_Report_DBHealth',
      'report_url' => 'net.ourpowerbase.report.dbhealth/dbhealth',
      'component' => 'CiviCampaign',
    ),
  ),
);
