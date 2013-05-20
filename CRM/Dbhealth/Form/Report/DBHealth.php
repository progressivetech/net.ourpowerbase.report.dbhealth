<?php

require_once 'CRM/Report/Form.php';

class CRM_Dbhealth_Form_Report_DBHealth extends CRM_Report_Form {
    protected $_summary                         = null;
    protected $_log_date_where_clause           = null;
    protected $_activity_where_clause           = null;
    
    function __construct( ) {		

    //Set Drupal roles for filtering
    $drupal_roles = array();
    $drupal_roles_query = 'SELECT * FROM role';
    $drupal_roles_dao = CRM_Core_DAO::executeQuery($drupal_roles_query, CRM_Core_DAO::$_nullArray);
    while ($drupal_roles_dao->fetch() ) {
      $drupal_roles[$drupal_roles_dao->rid] = $drupal_roles_dao->name;
    }

    	$this->activityTypes = CRM_Core_PseudoConstant::activityType(true, true);
        asort($this->activityTypes);
    	
        $this->_columns = 
            array( 'civicrm_contact' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'fields'    =>
                                      array( 'sort_name' => 
                                                  array( 'title'     => ts( 'Contact Name' ),
                                                         'required'  => true,),
                                             'id'        => 
                                                  array( 'no_display'=> true,
                                                         'required'  => true, ), ),
                                  'filters'   =>             
                                      array( 'sort_name' => 
                                                  array( 'title'     => ts( 'Contact Name' ),
                                                         'type'      => CRM_Utils_Type::T_STRING ), ),
                                  'grouping'  => 'user-fields', ),

                   'modified_contact' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'alias'     => 'modified_contact',
                                  'fields'    =>
                                      array( 'modified_contact_id'        => 
                                                  array( 'title'     => ts( 'Modified Records' ), 
                                                         'name'      => 'id',
                                                         'required'  => true, ), ),
                                  'grouping'  => 'user-fields', ),

                   'modified_individual' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'alias'     => 'modified_individual',
                                  'fields'    =>
                                      array( 'modified_individual_id'        => 
                                                  array( 'title'     => ts( 'Modified Individuals' ), 
                                                         'name'      => 'id', ), ),
                                  'grouping'  => 'user-fields', ),

                   'modified_organization' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'alias'     => 'modified_organization',
                                  'fields'    =>
                                      array( 'modified_organization_id'        => 
                                                  array( 'title'     => ts( 'Modified Organizations' ), 
                                                         'name'      => 'id', ), ),
                                  'grouping'  => 'user-fields', ),

                   'modified_household' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'alias'     => 'modified_household',
                                  'fields'    =>
                                      array( 'modified_household_id'        => 
                                                  array( 'title'     => ts( 'Modified Households' ), 
                                                         'name'      => 'id', ), ),
                                  'grouping'  => 'user-fields', ),

                   'activity_contact' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'alias'     => 'activity_contact',
                                  'fields'    =>
                                      array( 'activity_contact_id'        => 
                                                  array( 'title'     => ts( 'Sourced Activities' ), 
                                                         'name'      => 'id',
                                                         'required'  => true, ), ),
                                  'grouping'  => 'user-fields', ),

                   'assigned_contact' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'alias'     => 'assigned_contact',
                                  'fields'    =>
                                      array( 'assigned_contact_id'        => 
                                                  array( 'title'     => ts( 'Assigned Activities' ), 
                                                         'name'      => 'id',
                                                         'required'  => true, ), ),
                                  'grouping'  => 'user-fields', ),

                   'drupal_users' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'fields'    =>
                                      array( 'uid'       => 
                                                 array( 'no_display' => true,
                                                        'required'   => true,
                                                        'dbAlias'    => 'drupal_users.uid', ),
                                             'name'      => 
                                                 array( 'title'      => ts('Username'),
                                                        'required'   => true, 
                                                        'dbAlias'    => 'drupal_users.name', ), 
                                             'access'    => 
                                                 array( 'title'      => ts('Last Access'),
                                                        'dbAlias'    => 'drupal_users.access', ), ),
                                  'grouping'  => 'user-fields', ),

                   'drupal_role' =>
                           array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                  'fields'    =>
                                      array( 'name'      => 
                                                 array( 'title'      => ts('Role'),
                                                        'dbAlias'    => 'drupal_role.name', ), ),
                                  'filters'   =>   
                                      array('rid'         => 
                                                 array( 'title'        => ts( 'User Role' ),
                                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                                        'options'      => $drupal_roles,
                                                        'alias'        => 'drupal_role',
                                                        'dbAlias'      => 'drupal_role.rid', ), ),
                                  'grouping'  => 'user-fields', ),

                   'civicrm_log' => 
                           array( 'dao'       => 'CRM_Core_DAO_Log',
                                  'filters'   =>   
                                      array(  'modified_date' => 
                                                 array( 'operatorType' => CRM_Report_Form::OP_DATE,
                                                        'title'        => ts('Record Creation or Modification Date'), ), ),
                                  'grouping'  => 'stat-fields', ),

                   'civicrm_activity' =>
                           array( 'dao'       => 'CRM_Activity_DAO_Activity',
                                  'filters' =>   
                                      array('activity_date_time'  => 
                                                 array( 'operatorType'      => CRM_Report_Form::OP_DATE,
                                                        'title'        => ts( 'Activity Date' ), ),
                                            'activity_type_id'  => 
                                                 array( 'title'        => ts( 'Activity Type' ),
                                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                                        'options'      => $this->activityTypes, ), ),
                                  'grouping' => 'stat-fields', ),

                   );

        parent::__construct( );
    }
    
    function preProcess( ) {
        parent::preProcess( );
    }
    
    function select( ) {
        $select = array( );
        $this->_columnHeaders = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {

                        // only include statistics columns if set
                        if ( CRM_Utils_Array::value('statistics', $field) ) {
                            foreach ( $field['statistics'] as $stat => $label ) {
                                switch (strtolower($stat)) {
                                case 'sum':
                                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  = 
                                        $field['type'];
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'count':
                                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'avg':
                                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  =  
                                        $field['type'];
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                }
                            }
                        } else {
                          $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                          $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value( 'type', $field );
                          $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value( 'title', $field );

                        }
                    }
                }
            }
        }

        $this->_select = "SELECT " . implode( ', ', $select ) . " ";
    }

    static function formRule( $fields, $files, $self ) {  
        $errors = $grouping = array( );
        return $errors;
    }

    function from( ) {

        // This report assumes the CMS tables are in the same database as CiviCRM
        // Is there a non-hacky way to get the CMS database name when not?

        $this->_from = "
          FROM civicrm_contact {$this->_aliases['civicrm_contact']} 
          LEFT JOIN civicrm_contact {$this->_aliases['modified_contact']}
            ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['modified_contact']}.id
          LEFT JOIN civicrm_contact {$this->_aliases['modified_individual']}
            ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['modified_individual']}.id
          LEFT JOIN civicrm_contact {$this->_aliases['modified_organization']}
            ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['modified_organization']}.id
          LEFT JOIN civicrm_contact {$this->_aliases['modified_household']}
            ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['modified_household']}.id
          LEFT JOIN civicrm_contact {$this->_aliases['activity_contact']}
            ON {$this->_aliases['modified_contact']}.id = {$this->_aliases['activity_contact']}.id
          LEFT JOIN civicrm_contact {$this->_aliases['assigned_contact']}
            ON {$this->_aliases['activity_contact']}.id = {$this->_aliases['assigned_contact']}.id
          INNER JOIN civicrm_uf_match
            ON {$this->_aliases['assigned_contact']}.id = civicrm_uf_match.contact_id
          LEFT JOIN users drupal_users
            ON civicrm_uf_match.uf_id = drupal_users.uid
          LEFT JOIN users_roles drupal_users_roles
            ON drupal_users.uid = drupal_users_roles.uid
          LEFT JOIN role drupal_role
            ON drupal_users_roles.rid = drupal_role.rid
        ";
            
    }

    function where( ) {
        $clauses = array( );
        $this->_having = '';
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('filters', $table)  ) {
                foreach ( $table['filters'] as $fieldName => $field ) {
                    $clause = null;
                    $operatorType = CRM_Utils_Array::value('operatorType', $field);
                    if ( $operatorType & CRM_Report_Form::OP_DATE ) {
                        $relative = CRM_Utils_Array::value( "{$fieldName}_relative", $this->_params );
                        $from     = CRM_Utils_Array::value( "{$fieldName}_from"    , $this->_params );
                        $to       = CRM_Utils_Array::value( "{$fieldName}_to"      , $this->_params );

                        if ($fieldName == 'modified_date' &&
                            ( !empty($relative) || !empty($from)  || !empty($to) ) ) {
                          $this->_log_date_where_clause = 'AND ' . $this->dateClause( $field['dbAlias'], $relative, $from, $to );
                        } 

                        if ($fieldName == 'activity_date_time' &&
                            ( !empty($relative) || !empty($from)  || !empty($to) ) ) {
                          $this->_activity_where_clause .= 'AND ' . $this->dateClause( $field['dbAlias'], $relative, $from, $to );
                        }
                    } else {
                        $op = CRM_Utils_Array::value( "{$fieldName}_op", $this->_params );
                        if ( $op ) {
                          if ($fieldName == 'activity_type_id' &&
                              $this->_params['activity_type_id_value'] != NULL ) {
                                $this->_activity_where_clause .= ' AND ' .
                                  $this->whereClause( $field,
                                                      $op,
                                                      CRM_Utils_Array::value( "{$fieldName}_value", $this->_params ),
                                                      CRM_Utils_Array::value( "{$fieldName}_min", $this->_params ),
                                                      CRM_Utils_Array::value( "{$fieldName}_max", $this->_params ) );
                          } else {
                            $clause = 
                                $this->whereClause( $field,
                                                $op,
                                                CRM_Utils_Array::value( "{$fieldName}_value", $this->_params ),
                                                CRM_Utils_Array::value( "{$fieldName}_min", $this->_params ),
                                                CRM_Utils_Array::value( "{$fieldName}_max", $this->_params ) );
                          }
                        }
                    }

                    if ( ! empty( $clause ) ) {
                        $clauses[ ] = $clause;
                    }
                }
            }
        }

        //Exclude admin and civicron users from query.  No need to include non-active users.
        $clauses[] = "drupal_users.name != 'admin' AND drupal_users.name != 'civicron'";
        if ($clauses != NULL) {
          $this->_where = "WHERE " . implode( ' AND ', $clauses );
        }
        
    }
    
    function groupBy( ) {
      $this->_groupBy = "
        GROUP BY {$this->_aliases['civicrm_contact']}.id
      ";
    }
    
    function orderBy( ) {
      $this->_orderBy = "
        ORDER BY {$this->_aliases['civicrm_contact']}.sort_name
      ";
    }

    function getTemplateName( ) {
      return 'templates/DBHealth.tpl' ;
    }

    function statistics( &$rows) {
        $statistics = array();

        $count = count($rows);
    
        if ( $this->_rollup && ($this->_rollup != '') && $this->_grandFlag ) {
            $count++;
        }

        $this->countStat  ( $statistics, $count );

        $this->groupByStat( $statistics );

        $this->filterStat ( $statistics );

        //Count Contacts
        $contact_count_query = "SELECT COUNT(id) 
                                  FROM civicrm_contact";
        $contact_count = CRM_Core_DAO::singleValueQuery( $contact_count_query );


        //Count Contact with Email Addresses
        $contact_email_count_query = "SELECT COUNT(cc.id) 
                                        FROM civicrm_contact cc 
                                  INNER JOIN civicrm_email ce 
                                          ON cc.id = ce.contact_id
                                         AND ce.is_primary = 1";
        $contact_email_count = CRM_Core_DAO::singleValueQuery( $contact_email_count_query );
        $emailPercent = round(($contact_email_count/$contact_count)*100, 2);

        $statistics['counts']['emailPercent']['title'] = '% of contacts with email addresses';
        $statistics['counts']['emailPercent']['value'] = $emailPercent;


        //Count Contact with Phone Numbers
        $contact_phone_count_query = "SELECT COUNT(cc.id) 
                                        FROM civicrm_contact cc 
                                  INNER JOIN civicrm_phone cp 
                                          ON cc.id = cp.contact_id
                                         AND cp.is_primary = 1";
        $contact_phone_count = CRM_Core_DAO::singleValueQuery( $contact_phone_count_query );
        $phonePercent = round(($contact_phone_count/$contact_count)*100, 2);

        $statistics['counts']['phonePercent']['title'] = '% of contacts with phone numbers';
        $statistics['counts']['phonePercent']['value'] = $phonePercent;


        return $statistics;

    }

    function limit( $rowCount = NULL ) {
        $this->_limit  = "";
    }
    
    function alterDisplay( &$rows ) {
        require_once 'CRM/Core/DAO.php';
        
        // custom code to alter rows
        $entryFound = false;
        foreach ( $rows as $rowNum => $row ) {
            // convert display name to links
            if ( array_key_exists('civicrm_contact_sort_name', $row) && 
                 array_key_exists('civicrm_contact_id', $row) ) {
                $contact_url = CRM_Utils_System::url( 'civicrm/contact/view', 
                                              'reset=1&cid=' . $row['civicrm_contact_id'],
                                              $this->_absoluteUrl );
                $rows[$rowNum]['civicrm_contact_sort_name_link' ] = $contact_url;
                $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact details for this contact.");
                $entryFound = true;
            }

            // Count modified Contacts
            if ( array_key_exists('modified_contact_modified_contact_id', $row)) {
                $modified_contact_id = $row['modified_contact_modified_contact_id'];
                $modified_contact_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
                                                 FROM civicrm_contact
                                                 JOIN civicrm_log log_civireport 
                                                   ON civicrm_contact.id = log_civireport.entity_id
                                                WHERE modified_id = $modified_contact_id
                                                  AND data LIKE 'civicrm_contact%'
                                                      $this->_log_date_where_clause";
                $modified_contact_count = CRM_Core_DAO::singleValueQuery( $modified_contact_count_sql );
                $rows[$rowNum]['modified_contact_modified_contact_id'] = $modified_contact_count;
            }

            // Count modified Individuals
            if ( array_key_exists('modified_individual_modified_individual_id', $row)) {
                $modified_individual_id = $row['modified_individual_modified_individual_id'];
                $modified_individual_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
                                                    FROM civicrm_contact
                                                    JOIN civicrm_log log_civireport 
                                                      ON civicrm_contact.id = log_civireport.entity_id
                                                   WHERE log_civireport.modified_id = $modified_individual_id 
                                                     AND log_civireport.data LIKE 'civicrm_contact%'
                                                     AND civicrm_contact.contact_type = 'Individual'
                                                         $this->_log_date_where_clause";
                $modified_individual_count = CRM_Core_DAO::singleValueQuery( $modified_individual_count_sql );
                $rows[$rowNum]['modified_individual_modified_individual_id'] = $modified_individual_count;
            }

            // Count modified Organizations
            if ( array_key_exists('modified_organization_modified_organization_id', $row)) {
                $modified_organization_id = $row['modified_organization_modified_organization_id'];
                $modified_organization_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
                                                      FROM civicrm_contact
                                                      JOIN civicrm_log log_civireport 
                                                        ON civicrm_contact.id = log_civireport.entity_id
                                                     WHERE log_civireport.modified_id = $modified_organization_id 
                                                       AND log_civireport.data LIKE 'civicrm_contact%'
                                                       AND civicrm_contact.contact_type = 'Organization'
                                                           $this->_log_date_where_clause";
                $modified_organization_count = CRM_Core_DAO::singleValueQuery( $modified_organization_count_sql );
                $rows[$rowNum]['modified_organization_modified_organization_id'] = $modified_organization_count;
            }

            // Count modified contacts
            if ( array_key_exists('modified_household_modified_household_id', $row)) {
                $modified_household_id = $row['modified_household_modified_household_id'];
                $modified_household_count_sql = "SELECT COUNT(DISTINCT(civicrm_contact.id))
                                                   FROM civicrm_contact
                                                   JOIN civicrm_log log_civireport 
                                                     ON civicrm_contact.id = log_civireport.entity_id
                                                  WHERE log_civireport.modified_id = $modified_household_id 
                                                    AND log_civireport.data LIKE 'civicrm_contact%'
                                                    AND civicrm_contact.contact_type = 'Household'
                                                        $this->_log_date_where_clause";
                $modified_household_count = CRM_Core_DAO::singleValueQuery( $modified_household_count_sql );
                $rows[$rowNum]['modified_household_modified_household_id'] = $modified_household_count;
            }

            // Count activities created
            if ( array_key_exists('activity_contact_activity_contact_id', $row)) {
                $activity_contact_id = $row['activity_contact_activity_contact_id'];
                $activity_contact_count_sql = "SELECT COUNT(id) 
                                               FROM civicrm_activity activity_civireport 
                                               WHERE source_contact_id = $activity_contact_id $this->_activity_where_clause";
                $activity_contact_count = CRM_Core_DAO::singleValueQuery( $activity_contact_count_sql );
                $rows[$rowNum]['activity_contact_activity_contact_id'] = $activity_contact_count;
            }

            // Count activities assigned
            if ( array_key_exists('assigned_contact_assigned_contact_id', $row)) {
                $assigned_contact_id = $row['assigned_contact_assigned_contact_id'];
                $assigned_contact_count_sql = "SELECT COUNT(civicrm_activity_assignment.id) 
                                               FROM civicrm_activity_assignment 
                                               JOIN civicrm_activity activity_civireport
                                               ON activity_civireport.id = civicrm_activity_assignment.activity_id
                                               WHERE assignee_contact_id = $assigned_contact_id $this->_activity_where_clause";
                $assigned_contact_count = CRM_Core_DAO::singleValueQuery( $assigned_contact_count_sql );
                $rows[$rowNum]['assigned_contact_assigned_contact_id'] = $assigned_contact_count;
            }

            if ( array_key_exists('drupal_users_name', $row) && 
                 array_key_exists('drupal_users_uid', $row)) {
                $user_url = CRM_Utils_System::url( 'user/' . $row['drupal_users_uid']);
                $rows[$rowNum]['drupal_users_name_link' ] = $user_url;
                $rows[$rowNum]['drupal_users_name_hover'] = ts("View User Account details for this contact");
                unset($rows[$rowNum]['drupal_users_uid']);
            }

            if ( array_key_exists('drupal_users_access', $row)) {
                $access_date = date( 'F j, Y', $row['drupal_users_access'] );
                $rows[$rowNum]['drupal_users_access' ] = $access_date;
            }

            // skip looking further in rows, if first row itself doesn't 
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
        }
    }
}
