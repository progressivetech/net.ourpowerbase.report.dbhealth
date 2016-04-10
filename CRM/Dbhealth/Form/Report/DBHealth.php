<?php

class CRM_Dbhealth_Form_Report_DBHealth extends CRM_Report_Form {
  protected $_summary = NULL;

  // We build a dynamic set of where clause fragments depending
  // on what is requested
  protected $_log_date_where_clause = NULL;
  protected $_activity_where_clause = NULL;

  // The main where clause displays all enabled users, we keep
  // a user-supplied where clause to calculate the totals in
  // alterDisplay
  protected $_totals_where_clause = NULL;

  protected $_cms = NULL;
  protected $_cmsDbName = NULL;

  function getCmsRoles() {
    $cms = $this->_cms;
    $cmsDbName = $this->_cmsDbName;

    if ($cms == 'Drupal') {
      $drupal_roles = array();
      $drupal_roles_query = "SELECT * FROM $cmsDbName.role";
      $drupal_roles_dao = CRM_Core_DAO::executeQuery($drupal_roles_query, CRM_Core_DAO::$_nullArray);
      while ($drupal_roles_dao->fetch()) {
        $drupal_roles[$drupal_roles_dao->rid] = $drupal_roles_dao->name;
      }
      $roles = $drupal_roles;
    }
    if ($cms == 'WordPress') {
      require_once ABSPATH . WPINC . '/pluggable.php';
      $wp_roles = wp_roles()->roles;
      $roles = '';
      foreach ($wp_roles as $k=>$role) {
        $roles[] = $role['name'];
      }
    }
  return $roles;
  }


  function __construct() {
    // Get the CMS type and the name of the CMS database.
    $config = &CRM_Core_Config::singleton();
    if ($config->userFrameworkDSN) {
      $cmsDb = DB::connect($config->userFrameworkDSN);
      $this->_cmsDbName = $cmsDb->dsn['database'];
      $this->_cms = $config->userFramework;
      // For the purposes of this extension, all Drupal-likes are Drupal.
      if ($this->_cms == 'Drupal6' || $this->_cms == 'Drupal8' || $this->_cms == 'Backdrop') {
        $this->_cms = 'Drupal';
      }
    }

    $this->_exposeContactID = FALSE;
    $cms_roles = $this->getCmsRoles();
    $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    asort($this->activityTypes);
    if ($this->_cms == 'Drupal') {
      $usernameField = 'name';
      $idField = 'uid';
      $accessField = 'access';
      $roleField = 'rid';
      $roleDbAlias = 'GROUP_CONCAT(cms_role.name)';
    }
    if ($this->_cms == 'WordPress') {
      $usernameField = 'display_name';
      $idField = 'ID';
      $accessField = 'ID';
      $roleField = 'meta_value';
      $roleDbAlias = 'cms_role.meta_value';
    }

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields'  => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE),
          'id' => array(
            'no_display'=> TRUE,
            'title' => ts('Contact Id'),
            'required'  => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'type' => CRM_Utils_Type::T_STRING
          ),
        ),
        'grouping'  => 'user-fields'
      ),
      'modified_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'modified_contact',
        'fields'  => array(
          'modified_contact_id' => array(
            'title' => ts('Modified Records'),
            'name' => 'id',
          ),
        ),
        'grouping'  => 'user-fields',
      ),
      'modified_individual' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'modified_individual',
        'fields' => array(
          'modified_individual_id' => array(
            'title' => ts('Modified Individuals'),
            'name' => 'id',
          ),
        ),
        'grouping'  => 'user-fields',
      ),
      'modified_organization' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'modified_organization',
        'fields' => array(
          'modified_organization_id' => array(
            'title' => ts('Modified Organizations'),
            'name' => 'id',
          ),
        ),
        'grouping'  => 'user-fields',
      ),
      'modified_household' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'modified_household',
        'fields'  => array(
          'modified_household_id' => array(
            'title' => ts('Modified Households'),
            'name' => 'id',
          ),
        ),
        'grouping'  => 'user-fields',
      ),
      'activity_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'activity_contact',
        'fields'  => array(
          'activity_contact_id' => array(
            'title' => ts('Sourced Activities'),
            'name' => 'id',
          ),
        ),
        'grouping'  => 'user-fields',
      ),
      'assigned_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'assigned_contact',
        'fields'  => array(
          'assigned_contact_id' => array(
            'title' => ts('Assigned Activities'),
            'name' => 'id',
          ),
        ),
        'grouping'  => 'user-fields',
      ),
      'cms_users' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'uid' => array(
            'no_display' => TRUE,
            'dbAlias' => "cms_users.$idField",
          ),
          'username' => array(
            'title' => ts('Username'),
            'dbAlias'  => "cms_users.$usernameField",
          ),
          'access'  => array(
            'title' => ts('Last Access'),
            'dbAlias'  => "cms_users.$accessField",
          ),
        ),
        'grouping'  => 'user-fields',
      ),
      'cms_role' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields'  => array(
          'name' => array(
            'title' => ts('Role'),
            'dbAlias'  => $roleDbAlias,
          ),
        ),
        'filters' => array(
          'rid' => array(
            'title' => ts('User Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $cms_roles,
            'alias' => 'cms_role',
            'dbAlias' => "cms_role.$roleField",
          ),
        ),
        'grouping' => 'user-fields',
      ),
      'civicrm_log' => array(
        'dao' => 'CRM_Core_DAO_Log',
        'filters' => array(
          'modified_date' => array(
            'operatorType' => CRM_Report_Form::OP_DATE,
            'title' => ts('Record Creation or Modification Date'),
          ),
        ),
        'grouping'  => 'stat-fields',
      ),
      'civicrm_activity' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'filters' => array(
          'activity_date_time' => array(
            'operatorType' => CRM_Report_Form::OP_DATE,
            'title' => ts('Activity Date'),
          ),
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ),
        ),
        'grouping' => 'stat-fields',
      ),
    );
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();
    // Only do a query on the name, contact id, username, last access, and role
    // The actual statistics are generated in the alterResults function.
    $allowed_fields = array(
      'civicrm_contact_sort_name',
      'civicrm_contact_id',
      'cms_users_username',
      'cms_users_uid',
      'cms_users_access',
      'cms_role_name'
    );
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
             CRM_Utils_Array::value($fieldName, $this->_params['fields'])) {
            if(in_array($tableName . '_' . $fieldName, $allowed_fields)) {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";

            }
            else{
              // Set their value to 0 because it will be replaced below.
              $select[] = "0 as {$tableName}_{$fieldName}";
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    return $errors;
  }

  function from() {
    // This report assumes the CMS tables are in the same database as CiviCRM
    // Is there a non-hacky way to get the CMS database name when not?

    $this->_from = "
      FROM civicrm_contact {$this->_aliases['civicrm_contact']}
      INNER JOIN civicrm_uf_match ON {$this->_aliases['civicrm_contact']}.id = civicrm_uf_match.contact_id";
      if ($this->_cms == 'Drupal') {
        $this->_from .= " LEFT JOIN `$this->_cmsDbName`.users cms_users
          ON civicrm_uf_match.uf_id = cms_users.uid
          LEFT JOIN `$this->_cmsDbName`.users_roles cms_users_roles
          ON cms_users.uid = cms_users_roles.uid
          LEFT JOIN `$this->_cmsDbName`.role cms_role
          ON cms_users_roles.rid = cms_role.rid";
      }
      if ($this->_cms == 'WordPress') {
        $this->_from .= " LEFT JOIN `$this->_cmsDbName`.wp_users cms_users
        ON civicrm_uf_match.uf_id = cms_users.ID
        LEFT JOIN `$this->_cmsDbName`.wp_usermeta cms_role
        ON cms_users.ID = cms_role.user_id";
      }
  }

  function where() {
    $clauses = array();
    $this->_having = '';
    // Build a $_log_date_where_clause and $_activity_where_clause
    // for use in the alterDisplay function below.
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $operatorType = CRM_Utils_Array::value('operatorType', $field);
          if ($operatorType & CRM_Report_Form::OP_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            if ($fieldName == 'modified_date' &&
              (!empty($relative) || !empty($from)  || !empty($to))) {
              $this->_log_date_where_clause = 'AND ' . $this->dateClause($field['dbAlias'], $relative, $from, $to);
            }

            if ($fieldName == 'activity_date_time' &&
              (!empty($relative) || !empty($from)  || !empty($to))) {
              $this->_activity_where_clause .= 'AND ' . $this->dateClause($field['dbAlias'], $relative, $from, $to);
            }
          } else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
              if ($fieldName == 'activity_type_id' &&
                $this->_params['activity_type_id_value'] != NULL) {
                $this->_activity_where_clause .= ' AND ' . $clause;
              }
              else {
                // Add to general where - this is just contact name and user role
                if(!empty($clause)) $clauses[] = $clause;
              }
            }
          }
        }
      }
    }
    // The real $_where clause includes all users that are enabled and is not modifiable by the user.
    // (Exclude admin and civicron users from query.  No need to include non-active users.)
    if ($this->_cms == 'Drupal') {
      $this->_where = " WHERE cms_users.name != 'iiiiadmin' AND cms_users.name != 'civicron' AND cms_users.status = 1 ";
    }
    if(count($clauses) > 0) {
      $this->_where .= ' AND ' . implode(' AND ', $clauses) . ' ';
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id ";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name ";
  }

  function getTemplateName() {
    return 'templates/DBHealth.tpl' ;
  }

  function statistics(&$rows) {
    $statistics = array();
    $count = count($rows);
    if ($this->_rollup && ($this->_rollup != '') && $this->_grandFlag) {
      $count++;
    }

    $this->countStat($statistics, $count);

    $this->groupByStat($statistics);

    $this->filterStat ($statistics);

    //Count Contacts
    $contact_count_query = "SELECT COUNT(id) FROM civicrm_contact WHERE is_deleted = 0";
    $contact_count = CRM_Core_DAO::singleValueQuery($contact_count_query);


    //Count Contact with Email Addresses
    $contact_email_count_query = "SELECT COUNT(cc.id)
      FROM civicrm_contact cc
      INNER JOIN civicrm_email ce
      ON cc.id = ce.contact_id
      AND ce.is_primary = 1 AND
      cc.is_deleted = 0";
    $contact_email_count = CRM_Core_DAO::singleValueQuery($contact_email_count_query);
    $emailPercent = $contact_email_count/$contact_count*100;

    $statistics['counts']['emailPercent']['title'] = '% of contacts with email addresses';
    $statistics['counts']['emailPercent']['value'] = $emailPercent;


    //Count Contact with Phone Numbers
    $contact_phone_count_query = "SELECT COUNT(cc.id)
      FROM civicrm_contact cc
      INNER JOIN civicrm_phone cp
      ON cc.id = cp.contact_id
      AND cp.is_primary = 1 AND
      cc.is_deleted = 0";
    $contact_phone_count = CRM_Core_DAO::singleValueQuery($contact_phone_count_query);
    $phonePercent = $contact_phone_count/$contact_count*100;

    $statistics['counts']['phonePercent']['title'] = '% of contacts with phone numbers';
    $statistics['counts']['phonePercent']['value'] = $phonePercent;

    return $statistics;

  }

  function limit($rowCount = NULL) {
    $this->_limit  = "";
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    foreach ($rows as $rowNum => $row) {
      $contact_id = intval($row['civicrm_contact_id']);
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
         array_key_exists('civicrm_contact_id', $row)) {
        $contact_url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl);
        $rows[$rowNum]['civicrm_contact_sort_name_link' ] = $contact_url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      // Count modified Contacts
      if (array_key_exists('modified_contact_modified_contact_id', $row)) {
        $modified_contact_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
          FROM civicrm_contact
          JOIN civicrm_log log_civireport ON civicrm_contact.id = log_civireport.entity_id
          WHERE modified_id = $contact_id
          AND data LIKE 'civicrm_contact%'
          $this->_log_date_where_clause";
        $modified_contact_count = CRM_Core_DAO::singleValueQuery($modified_contact_count_sql);
        $rows[$rowNum]['modified_contact_modified_contact_id'] = $modified_contact_count;
      }

      // Count modified Individuals
      if (array_key_exists('modified_individual_modified_individual_id', $row)) {
        $modified_individual_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
          FROM civicrm_contact
          JOIN civicrm_log log_civireport ON civicrm_contact.id = log_civireport.entity_id
          WHERE log_civireport.modified_id = $contact_id
          AND log_civireport.data LIKE 'civicrm_contact%'
          AND civicrm_contact.contact_type = 'Individual'
          $this->_log_date_where_clause";
        $modified_individual_count = CRM_Core_DAO::singleValueQuery($modified_individual_count_sql);
        $rows[$rowNum]['modified_individual_modified_individual_id'] = $modified_individual_count;
      }

      // Count modified Organizations
      if (array_key_exists('modified_organization_modified_organization_id', $row)) {
        $modified_organization_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
          FROM civicrm_contact
          JOIN civicrm_log log_civireport ON civicrm_contact.id = log_civireport.entity_id
          WHERE log_civireport.modified_id = $contact_id
          AND log_civireport.data LIKE 'civicrm_contact%'
          AND civicrm_contact.contact_type = 'Organization'
          $this->_log_date_where_clause";
        $modified_organization_count = CRM_Core_DAO::singleValueQuery($modified_organization_count_sql);
        $rows[$rowNum]['modified_organization_modified_organization_id'] = $modified_organization_count;
      }

      // Count modified contacts
      if (array_key_exists('modified_household_modified_household_id', $row)) {
        $modified_household_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
          FROM civicrm_contact
          JOIN civicrm_log log_civireport ON civicrm_contact.id = log_civireport.entity_id
          WHERE log_civireport.modified_id = $contact_id
          AND log_civireport.data LIKE 'civicrm_contact%'
          AND civicrm_contact.contact_type = 'Household'
          $this->_log_date_where_clause";
        $modified_household_count = CRM_Core_DAO::singleValueQuery($modified_household_count_sql);
        $rows[$rowNum]['modified_household_modified_household_id'] = $modified_household_count;
      }

      // Count activities created
      if (array_key_exists('activity_contact_activity_contact_id', $row)) {
        // NOTE: record_type_id = 2 for created activities
        $activity_contact_count_sql = "SELECT COUNT(civicrm_activity_contact.id)
          FROM civicrm_activity_contact
          JOIN civicrm_activity activity_civireport
          ON activity_civireport.id = civicrm_activity_contact.activity_id
          WHERE contact_id = $contact_id AND record_type_id = 2 $this->_activity_where_clause";
        $activity_contact_count = CRM_Core_DAO::singleValueQuery($activity_contact_count_sql);
        $rows[$rowNum]['activity_contact_activity_contact_id'] = $activity_contact_count;
      }

      // Count activities assigned
      if (array_key_exists('assigned_contact_assigned_contact_id', $row)) {
        // NOTE: record_type_id = 1 for assigned activities
        $assigned_contact_count_sql = "SELECT COUNT(civicrm_activity_contact.id)
          FROM civicrm_activity_contact
          JOIN civicrm_activity activity_civireport
          ON activity_civireport.id = civicrm_activity_contact.activity_id
          WHERE contact_id = $contact_id AND record_type_id = 1 $this->_activity_where_clause";
        $assigned_contact_count = CRM_Core_DAO::singleValueQuery($assigned_contact_count_sql);
        $rows[$rowNum]['assigned_contact_assigned_contact_id'] = $assigned_contact_count;
      }

      if (array_key_exists('cms_users_name', $row) &&
         array_key_exists('cms_users_uid', $row)) {
        $user_url = CRM_Utils_System::url('user/' . $row['cms_users_uid']);
        $rows[$rowNum]['cms_users_name_link' ] = $user_url;
        $rows[$rowNum]['cms_users_name_hover'] = ts("View User Account details for this contact");
        unset($rows[$rowNum]['cms_users_uid']);
      }

      if (array_key_exists('cms_users_access', $row)) {
        $access_date = date('F j, Y', $row['cms_users_access']);
        $rows[$rowNum]['cms_users_access' ] = $access_date;
      }
    }
  }
}
