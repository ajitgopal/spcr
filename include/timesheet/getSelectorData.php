<?php
  require("global.inc"); 
  require_once('timesheet/class.Timesheet.php');
  $timesheetObj   = new AkkenTimesheet($db);


  // Timesheets and Expenses Employees Drop down listing values
  if (isset($getEmployeeSearchVal)) {
    $data = '{"results":[';
    $module = $getModule;  
    $assign_start_date = $getServicedate; 
    $assign_end_date = $getServicedateto;
    $srchEmpName = $getEmployeeSearchVal;
    $fun = 'get'.$module.'EmployeeNames';
    $employees = $timesheetObj->{$fun}($username, $assign_start_date, $assign_end_date,$srchEmpName);

    if($empnames == '')
    {
      $empnames   = $timesheetObj->new_first_user;
    }
    
    if(count($employees) > 0){
      foreach ($employees as $empid => $empName) {
        $data .= '{"id":"'.$empid.'","text":"'.stripslashes($empName).'"},';
      }
    }else{
      $data .= '';
    }
    $data = trim($data,",").'],"count_filtered": '.count($employees).' }';
    echo $data;
  }

  // Assignments Employees Drop down listing values
  if (isset($getAsgnEmployeeSearchVal)) {
      $data = '{"results":[';
      if (isset($getAsgnEmployeeSearchVal['term'])) {
        $whrEmpSrch = " AND CONCAT(sno,'-',name) like '%".$getAsgnEmployeeSearchVal['term']."%' ";
      }
      if(!isset($getAsgnEmployeeSearchVal['term'])){
        $limit_val = " LIMIT 100";
      }
      $query="SELECT username,name, CONCAT(sno,' - ',name),sno FROM emp_list WHERE lstatus != 'DA' AND lstatus != 'INACTIVE' AND (empterminated!='Y' || UNIX_TIMESTAMP(IF(tdate='' || tdate IS NULL,NOW(),tdate))>UNIX_TIMESTAMP(NOW())) ".$whrEmpSrch." ORDER BY name ".$limit_val;  
      $result=mysql_query($query,$db);
      $empCount = 0;
      $names = array();
      while($myrow=mysql_fetch_row($result))
      {
        if($empCount == 0)
        {
          $usernamevals = $myrow[0];
        }
        $names[$myrow[0]] = $myrow[2];      
        $empCount++;
      }
      
      if(count($empCount) > 0){
        foreach ($names as $empid => $empName) {
          $data .= '{"id":"'.$empid.'","text":"'.stripslashes($empName).'"},';
        }
      }else{
        $data .= '';
      }
      $data = trim($data,",").'],"count_filtered": '.count($names).' }';
      echo $data;
  }

  // Multitimesheets - Employee drop down listing values
  if (isset($getMultiEmployeeSearchVal)) {
    $data = '{"results":[';
    if (isset($getMultiEmployeeSearchVal['term'])) {
      $whrEmpSrch = " AND CONCAT(".getEntityDispName("TRIM(CONCAT_WS(' ',emp_list.emp_lname,emp_list.emp_fname,emp_list.emp_mname))","emp_list.sno").",' - ', hrcon_jobs.assign_no) like '%".$getMultiEmployeeSearchVal['term']."%' ";
    }
    if(!isset($getMultiEmployeeSearchVal['term'])){
      $limit_val = " LIMIT 100";
    }
    $query="SELECT emp_list.username uid, CONCAT_WS(' ',emp_list.emp_lname,emp_list.emp_fname,emp_list.emp_mname), ".getEntityDispName("CONCAT_WS(' ',emp_list.emp_lname,emp_list.emp_fname,emp_list.emp_mname)","emp_list.sno").", hrcon_jobs.assign_no
    FROM emp_list,hrcon_jobs
     WHERE emp_list.username = hrcon_jobs.username
           AND emp_list.lstatus != 'DA'
           AND emp_list.lstatus != 'INACTIVE'
           AND (   emp_list.empterminated != 'Y'
                || (  UNIX_TIMESTAMP(DATE_FORMAT(emp_list.tdate, '%Y-%m-%d'))
                    - UNIX_TIMESTAMP()) > 0)
           AND hrcon_jobs.ustatus IN ('active', 'closed', 'cancel')
           AND hrcon_jobs.ustatus != ''
           AND hrcon_jobs.jtype != ''
           AND emp_list.emp_timesheet != 'Y' AND hrcon_jobs.jtype!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y')) <= '".$getServicedateto."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$getServicedatefrom."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$getServicedatefrom."'),1)))".$whrEmpSrch." 
      GROUP BY emp_list.username, emp_list.name, hrcon_jobs.assign_no
      ORDER BY trim(emp_list.name)".$limit_val;  
    $result=mysql_query($query,$db);
    $empCount = 0;
    $names = array();
    $asgnCounter = 0;
    while($myrow=mysql_fetch_row($result))
    {
      $names[$myrow[3]] = $myrow[2];      
       $empCount++;
      if(count($empCount) > 0){

      $str_name = html_tls_specialchars($myrow[2], ENT_QUOTES).' - '.$myrow[3];
      $data .= '{"id":"'.$myrow[0].'","text":"'.stripslashes($str_name).'"},';
      $asgnCounter++;
      if($asgnCounter == $totalAsgnArr[$myrow[0]])
      {
        $que1="SELECT eartype FROM hrcon_benifit WHERE username='".$myrow[0]."' AND ustatus='active'";
        $res1=mysql_query($que1,$db);
        if(mysql_num_rows($res1) > 0)
        {
          while($r=mysql_fetch_array($res1)) {
            
            $str_name = html_tls_specialchars($myrow[2], ENT_QUOTES).' - '.$r[0];
            $data .= '{"id":"'.$myrow[0].'","text":"'.stripslashes($str_name).'"},';
          }
        }
        $asgnCounter = 0;
      }/*else{
        //$str_name = html_tls_specialchars($myrow[2], ENT_QUOTES).' - '.$myrow[3];
        
      }*/
      
      }
      else{
          $data .= '';
        }
      
    }
    $data = trim($data,",").'],"count_filtered": '.count($names).' }';  
    echo $data;
  }

  



?>