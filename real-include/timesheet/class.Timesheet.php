<?php
/*
 *       
By	        :          Priyanka Varanasi
Modified Date	: June 27,2016
Purpose		: [#807524] Time sheet Issue - On clicking  the history link from logged css user , if the history relates to another css user , then it is displaying 0.0
Modified Details: Added one global variable to display the history's or backup's of logged user's only in DisplaybackupTimesheet(); for regular timesheets.
 * 
 */
 
class AkkenTimesheet
{
    public $db;
    public $userName;
    public $module;
    public $mysqlobj;
    public $new_first_user;
    public $assignments;
    public $assignmentIds;
    public $rateTypeCountSingle;
    public $accountingExport;
    public $mystr = array();
    public $hiddenBillable = array();
    public $clientcheckingArr = array();
    public $listOfAssignments = array(); // used to get the all assignment ids #817655
    public $eachrowidTotalValArr = array();
    public $eachrowidTotalPersonAttachmentValArr = array();

    function __construct($db)
    {
	$this->db = $db;
	require_once('timesheet/class.MysqlWraper.php');
	$this->mysqlobj = new MysqlWraper();
    }
    
    function sel($a,$b)
    {
	if($a==$b)
	{
	    return "selected";
	}
	else
	{
	    return "";
	}
    }
    
    function chk($a)
    {
	if($a=='N' || $a=='')
	{
	    return "";
	}
	else
	{
	    return "checked";
	}
    }    

    function disable($a)
    {
	if($a!='Y')
	{
	    return "disabled";    
	}
	else
	{
	    return "";
	}
    }
    
    function output($data)
    {
	echo "<pre>";
	print_r($data);
	echo "</pre>";
	echo "------------------------------<br>";
    }
    
    function buildEmpList($emp_array, $selectedEmpList='')
    {
		foreach ($emp_array as $emp_id => $emps)
		{
			$selEmp = $this->sel($emp_id, $selectedEmpList);		
			$emp_list .= '<option value="'.$emp_id.'" '.$selEmp.'>'.$emps.'</option>';
		}
		return $emp_list;
    }
    
    function getAccountingEmployeeNames($username, $assign_start_date, $assign_end_date,$searchEmpName='')
    {
		$assign_start_date = date('Y-m-d', strtotime($assign_start_date));
		$assign_end_date = date('Y-m-d', strtotime($assign_end_date));
		$whrEmpSrch = '';
		$limit_val = '';
		
		if (isset($searchEmpName['term'])) {
			$whrEmpSrch = " AND CONCAT(emp_list.sno, '-', emp_list.name) like '%".$searchEmpName['term']."%' ";
		}
		if(!isset($searchEmpName['term'])){
			$limit_val = " LIMIT 100";
		}

		$query="SELECT emp_list.username uid, emp_list.name name,
		CONCAT(emp_list.sno, '-', emp_list.name)
		FROM emp_list, hrcon_jobs
		WHERE emp_list.username = hrcon_jobs.username
		AND emp_list.lstatus != 'DA'
		AND emp_list.lstatus != 'INACTIVE'
		AND (emp_list.empterminated != 'Y' || (UNIX_TIMESTAMP(DATE_FORMAT(emp_list.tdate,'%Y-%m-%d'))-UNIX_TIMESTAMP())>0)
		AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y')) <= '".$assign_end_date."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assign_start_date."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assign_start_date."'),1)))
		AND hrcon_jobs.ustatus != ''
		AND hrcon_jobs.ustatus != 'backup'		
		AND hrcon_jobs.jtype != ''
		AND hrcon_jobs.pusername != ''
		AND emp_list.emp_timesheet != 'Y'
		
		".$whrEmpSrch."
		GROUP BY emp_list.username, emp_list.name
		ORDER BY emp_list.mtime DESC".$limit_val;	
		$result=$this->mysqlobj->query($query,$this->db);
		
		$new_first_user = "";
		$empCount = 0;
		$names = array();
		while($myrow=$this->mysqlobj->fetch_row($result))
		{
			if($empCount == 0)
			{
			$this->new_first_user = $myrow[0];
			}
			$names[$myrow[0]] = $myrow[2];	    
			$empCount++;
		}
		return $names;
    }
    
    function getClientEmployeeNames($username, $assign_start_date=NULL, $assign_end_date=NULL,$searchEmpName='')
    {
		$assign_start_date 	= date('Y-m-d', strtotime($assign_start_date));
		$assign_end_date 	= date('Y-m-d', strtotime($assign_end_date));
		
		$whrEmpSrch = '';
		$limit_val = '';
		
		
		if (isset($searchEmpName['term'])) {
			$whrEmpSrch = " AND CONCAT(emp_list.sno, '-', emp_list.name) like '%".$searchEmpName['term']."%' ";
		}
		if(!isset($searchEmpName['term'])){
			$limit_val = " LIMIT 100";
		}


		$sel		= "SELECT t2.con_id, t3.sno FROM staffacc_contact t1 INNER JOIN staffacc_contactacc t2
		ON t1.sno = t2.con_id INNER JOIN staffacc_cinfo t3 ON t3.username = t1.username WHERE t3.TYPE IN ('CUST', 'BOTH')
		AND t2.username = ".$username;
		$resselSno	= $this->mysqlobj->query($sel, $this->db);
		$rsselSno	= $this->mysqlobj->fetch_array($resselSno);
		
		$CtVal		= $rsselSno['con_id'];
		$ClVal		= $rsselSno['sno'];
		
		$sqlSelfPref	= "SELECT timesheet FROM selfservice_pref WHERE username='".$username."'";
		$resSelfPref	= $this->mysqlobj->query($sqlSelfPref,$this->db);
		$userSelfServicePref	= $this->mysqlobj->fetch_row($resSelfPref);
		$tsPreferencesCSS = $userSelfServicePref[0];


		$condCk_comp = "1=1";
		if(strpos($tsPreferencesCSS,"+3+")) // Customer CheckBox Selected
		{
			if(strpos($tsPreferencesCSS,"+9+")) // and Billing Contact
			{
				$showBillingCont	= " OR hrcon_jobs.bill_contact=$CtVal";				
			}
			$condCk_comp		= " (hrcon_jobs.client=".$ClVal." $showBillingCont)";
			$showEmplyoees		= "";
		}
		else
		{
			if(strpos($tsPreferencesCSS,"+4+"))
			{
				$chkContact 	= "hrcon_jobs.contact = $CtVal";
			}
			if(strpos($tsPreferencesCSS,"+5+"))
			{
				$chkReportTo 	= "hrcon_jobs.manager = $CtVal";
			}
			if(strpos($tsPreferencesCSS,"+9+"))
			{
				$chkAsgnBillContact	= "hrcon_jobs.bill_contact= $CtVal ";
			}
			
			if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact=="") // Only Contact
			{
				$showEmplyoees = "AND ($chkContact)";
			}
			else
			if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Only Reports To
			{				
				$showEmplyoees = "AND ($chkReportTo)";
			}
			else
			if($chkContact=="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Only Billing Contact
			{
				$showEmplyoees = "AND ($chkAsgnBillContact)";
			}
			else
			if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact=="") // Contact and Reports to
			{
				$showEmplyoees = "AND ($chkContact OR $chkReportTo)";
			}
			else
			if($chkContact!="" && $chkReportTo=="" && $chkAsgnBillContact!="") // Contact and Billing Contact
			{
				$showEmplyoees = "AND ($chkContact OR $chkAsgnBillContact)";
			}
			else
			if($chkContact=="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Reports to and Billing Contact
			{
				$showEmplyoees = "AND ($chkReportTo OR $chkAsgnBillContact)";
			}
			else
			if($chkContact!="" && $chkReportTo!="" && $chkAsgnBillContact!="") // Contact, reports to and billing contact
			{				
				$showEmplyoees = "AND ($chkContact OR $chkReportTo OR $chkAsgnBillContact)";
			}
		}
		$query	= "SELECT
				emp_list.username uid,
				emp_list.name name,
				CONCAT(emp_list.sno, '-', emp_list.name),
				GROUP_CONCAT(DISTINCT hrcon_jobs.client) AS empclients
			    FROM
				emp_list, hrcon_jobs
			    WHERE
				$condCk_comp
				AND emp_list.username = hrcon_jobs.username
				
				AND emp_list.lstatus != 'DA'
				AND emp_list.lstatus != 'INACTIVE' 
				AND (emp_list.empterminated != 'Y' || (UNIX_TIMESTAMP(DATE_FORMAT(emp_list.tdate,'%Y-%m-%d'))-UNIX_TIMESTAMP())>0)
				AND hrcon_jobs.ustatus IN ('active','closed','cancel')
				AND hrcon_jobs.ustatus != ''
				AND hrcon_jobs.jtype != ''
				AND hrcon_jobs.pusername!=''
				AND emp_list.emp_timesheet != 'Y'
				$showEmplyoees 		
				AND ((hrcon_jobs.ustatus IN ('active','closed','cancel')
				AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))
				<= '".$assign_end_date."')))
				AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>''
				AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assign_start_date."'),1))
				AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>''
				AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assign_start_date."'),1))	
				".$whrEmpSrch."
			    GROUP BY
				emp_list.username, emp_list.name
			    ORDER BY
				emp_list.mtime DESC ".$limit_val;
		$result=$this->mysqlobj->query($query,$this->db);

		$new_first_user = "";
		$empCount = 0;
		$names = array();
		while($myrow=$this->mysqlobj->fetch_row($result))
		{
			if($empCount == 0)
			{
			    $this->new_first_user = $myrow[0];
			}
			$names[$myrow[0]] = $myrow[2];
			$this->clientcheckingArr[$myrow[0]] = $myrow[3];
			$empCount++;
		}
		return $names;		
    }
    
    function getMyProfileEmployeeNames($username, $assign_start_date, $assign_end_date)
    {
		$this->new_first_user = $username;
		return $username;	
    }
    
   /* function GetDays($sStartDate, $sEndDate)
    {  
	//$sStartDate = gmdate("Y-m-d", strtotime($sStartDate));  
	//$sEndDate = gmdate("Y-m-d", strtotime($sEndDate));
	$sStartDate = gmdate("m/d/Y", strtotime($sStartDate));  
	$sEndDate = gmdate("m/d/Y", strtotime($sEndDate));  
	$aDays[] = $sStartDate." ".date('l', strtotime($sStartDate)); 
	$sCurrentDate = $sStartDate;  

	while($sCurrentDate < $sEndDate){  
		//$sCurrentDate = gmdate("Y-m-d", strtotime("+1 day", strtotime($sCurrentDate)));
		$sCurrentDate = gmdate("m/d/Y", strtotime("+1 day", strtotime($sCurrentDate)));  
		$aDays[] = $sCurrentDate." ".date('l', strtotime( $sCurrentDate));
	} 
	return $aDays;  
    }  */
    
	function getPayrollWeekendDay(){
		$sel="SELECT payperiod, stdhours,wdays,pdays,paydays,weekend_day,taxbasedon FROM cpaysetup WHERE STATUS='ACTIVE'";
		$res=$this->mysqlobj->query($sel, $this->db);
		$getWeekendDay= $this->mysqlobj->fetch_array($res);
		$WeekendDay = $getWeekendDay['weekend_day'];
		return $WeekendDay;
		
	}

	function GetDays($strDateFrom,$strDateTo)
	{
            // takes two dates formatted as YYYY-MM-DD and creates an
            // inclusive array of the dates between the from and to dates.

            // could test validity of dates here but I'm already doing
            // that in the main script
            $strDateFrom = gmdate("Y-m-d", strtotime($strDateFrom));
            $strDateTo = gmdate("Y-m-d", strtotime($strDateTo));
            $aryRange=array();

            $iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2),     substr($strDateFrom,8,2),substr($strDateFrom,0,4));
            $iDateTo=mktime(1,0,0,substr($strDateTo,5,2),     substr($strDateTo,8,2),substr($strDateTo,0,4));

            if ($iDateTo>=$iDateFrom)
            {
                array_push($aryRange,date('m/d/Y',$iDateFrom).' '.date('l', strtotime( date('m/d/Y',$iDateFrom))));// first entry
                while ($iDateFrom<$iDateTo)
                {
                    $iDateFrom_convert = date('Y-m-d',$iDateFrom);
					$iDateFrom+=strtotime("+1 day", $iDateFrom_convert);
					//$iDateFrom+=86400; // add 24 hours
                    //$next = $iDateFrom+=86400;
                    array_push($aryRange,date('m/d/Y',$iDateFrom).' '.date('l', strtotime( date('m/d/Y',$iDateFrom))));
                }
            }
		return $aryRange;
	}


    function getWeekdays($day)
    {
		$getWeekDays = array();
		$d = $this->getPayrollWeekendDay();
		#echo $last_monday = date("m/d/Y",strtotime($day." last Monday "));
		$last_monday = date("m/d/Y",strtotime($day." last ".$d ));
		$start_day = date('m/d/Y', strtotime(" -6 day",strtotime($last_monday)));
			
		if(date('l', strtotime($day))=='Monday')
		{
			$monday = date('m/d/Y', strtotime($last_monday))." ".date('l', strtotime($last_monday)); 
		}
		else
		{
			$monday = date('m/d/Y', strtotime(' -7 day',strtotime($last_monday)))." ".date('l', strtotime($last_monday)); 
		}
		for($i=0;$i<7; $i++)
		{
			$getWeekDays[] = date('m/d/Y', strtotime(" +".$i." day",strtotime($start_day)))." ".date('l', strtotime(" +".$i." day",strtotime($start_day)));
		}
		return $getWeekDays;
    }
	
    function getMonthlydays($month)
    {
        $selected_days_arr  = array();
        if($month == 'lastmonth')
		{
			$begin 	= new DateTime("first day of last month");
			$end 	= new DateTime("last day of last month");
		}
		else
		{
			$begin 	= new DateTime("first day of this month");
			$end 	= new DateTime("last day of this month");			
		}
		$end   	= $end->modify( '+1 day' ); 
        
        $daterange = new DatePeriod($begin, new DateInterval('P1D'), $end);

        foreach($daterange as $date){

			$selected_days_arr[]   = $date->format("m/d/Y")." ".$date->format("l");
             
        }
            
        return $selected_days_arr;
    }    
	
    //Added ts_type for Timesheet difference
    function getAssignments($employee, $asgnid='', $assignStartDate0, $assignEndDate0, $rowid,$module='', $tab_index = '', $inout_flag = false, $cval = '',$ts_type = '')
    {

		global $companyname, $username;
		$assignOptions = '';
		$asgn_ratetypes ='';
		$uom_asgn = 0;
		$shift_name = '';

		$assignStartDate = date('Y-m-d', strtotime($assignStartDate0));
		$assignEndDate = date('Y-m-d', strtotime($assignEndDate0));
		
		if($module == "Client" && $cval != '')
		{
			//getting contact value of css user
			$sel		= "SELECT t2.con_id, t3.sno FROM staffacc_contact t1 INNER JOIN staffacc_contactacc t2
					    ON t1.sno = t2.con_id INNER JOIN staffacc_cinfo t3 ON t3.username = t1.username WHERE t3.TYPE IN ('CUST', 'BOTH')
					    AND t2.username = ".$username;
			$resselSno	= $this->mysqlobj->query($sel, $this->db);
			$rsselSno	= $this->mysqlobj->fetch_array($resselSno);
			
			$CtVal		= $rsselSno['con_id'];
			$cssUserClient  = $rsselSno['sno'];
		    
			$client_cond = " AND client IN (".$cval.")";

			
			$userSelfServicePref 	= $this->getClientPrefs($username);
			$tsPreferencesCSS 	= $userSelfServicePref[7];
			if(strpos($tsPreferencesCSS,"+3+") && strpos($tsPreferencesCSS,"+9+"))
			{

			    $empClientcheckingArr 	= explode(",",$cval);
			    if(!in_array($cssUserClient, $empClientcheckingArr)) // employee loaded based on assignment billing contact
			    {
				$client_cond	= " AND (client IN (".$cval.") AND hrcon_jobs.bill_contact= $CtVal) ";
			    }
			    else // same client employee
			    {
				$client_cond	= " AND (client IN (".$cval.") OR hrcon_jobs.bill_contact= $CtVal) ";
			    }
			}
			else 
			{
			    if((strpos($tsPreferencesCSS,"+4+") || strpos($tsPreferencesCSS,"+5+")) && strpos($tsPreferencesCSS,"+9+"))
			    {
				$client_cond	= " AND (client IN (".$cval.") OR hrcon_jobs.bill_contact= $CtVal) ";
			    }
			    else
			    if(strpos($tsPreferencesCSS,"+9+"))
			    {
				$client_cond	= " AND (client IN (".$cval.") AND hrcon_jobs.bill_contact= $CtVal) ";
			    }			    
			}						
		}
		
		if($ts_type == 'UOM' || $ts_type == 'Custom'){//Query for new UOM timesheet getting the rate types with previous query
					  
			$zque ="SELECT hrcon_jobs.sno, client, project, jtype, pusername, jotype, date_format(str_to_date(s_date,'%m-%d-%Y'),'%m/%d/%Y'), 
			date_format(str_to_date(e_date,'%m-%d-%Y'),'%m/%d/%Y'), 
			GROUP_CONCAT(CONCAT_WS( '^^',multi_rates.ratemasterid,multi_rates.period) SEPARATOR '&&') AS mulrates, 
			endclient, shiftname  
			FROM hrcon_jobs  
			LEFT JOIN shift_setup ON shift_setup.sno = hrcon_jobs.shiftid  
			LEFT JOIN multiplerates_assignment multi_rates ON multi_rates.asgnid=hrcon_jobs.sno  
			WHERE username = '".$employee."'  AND pusername!=''  
			AND multi_rates.ratetype = 'billrate' 
			AND multi_rates.asgn_mode = 'hrcon' 
			AND multi_rates.STATUS = 'Active' 
			AND ((hrcon_jobs.ustatus IN ('active','closed','cancel')  
					AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."')))  
					AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))  AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))
				)  
			AND hrcon_jobs.jtype!='' ".$client_cond." GROUP BY multi_rates.asgnid ORDER BY ustatus,pusername";
		}
		else{
			$zque 	= "SELECT
						hrcon_jobs.sno,
						client,
						project,
						jtype,
						pusername,
						jotype,
						date_format(str_to_date(s_date,'%m-%d-%Y'),'%m/%d/%Y'),
						date_format(str_to_date(e_date,'%m-%d-%Y'),'%m/%d/%Y'),
						(SELECT shiftname FROM shift_setup WHERE shift_setup.sno = hrcon_jobs.shiftid) AS shiftName,
						LOWER(sl.state)						
					FROM
						hrcon_jobs
					LEFT JOIN staffacc_location sl On (hrcon_jobs.endclient = sl.sno)
					WHERE
						username = '".$employee."' AND
						pusername!='' AND
						((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))) AND
						hrcon_jobs.jtype!='' ".$client_cond."
					ORDER BY ustatus,pusername";
		}

		$zres=$this->mysqlobj->query($zque,$this->db);
		$zrowCount = mysql_num_rows($zres);
		if($zrowCount == 0){
			if($ts_type == 'UOM' || $ts_type == 'Custom'){//Query for new UOM timesheet getting the rate types with previous query					  
				$zque ="SELECT hrcon_jobs.sno, client, project, jtype, pusername, jotype, date_format(str_to_date(s_date,'%m-%d-%Y'),'%m/%d/%Y'), date_format(str_to_date(e_date,'%m-%d-%Y'),'%m/%d/%Y'), 
				GROUP_CONCAT(CONCAT_WS( '^^',multi_rates.ratemasterid,multi_rates.period) SEPARATOR '&&') AS mulrates, endclient, shiftname 
				FROM hrcon_jobs 
				LEFT JOIN shift_setup ON shift_setup.sno = hrcon_jobs.shiftid  
				LEFT JOIN multiplerates_assignment multi_rates ON multi_rates.asgnid=hrcon_jobs.sno  
				WHERE username = '".$employee."'  AND pusername!='' 
				AND multi_rates.ratetype = 'billrate' AND multi_rates.asgn_mode = 'hrcon' 
				AND multi_rates.STATUS = 'Active' 
				AND ((hrcon_jobs.ustatus IN ('active','closed','cancel')  AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."'))) 
					AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))  
					AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1)))  
				AND hrcon_jobs.jtype!='' ".$client_cond." GROUP BY multi_rates.asgnid 
				ORDER BY ustatus,pusername";
				$zres=$this->mysqlobj->query($zque,$this->db);
				$zrowCount = mysql_num_rows($zres);
			}
		}
		$this->assignments = array();
		$this->assignmentIds = array();
			
		while($zrow=$this->mysqlobj->fetch_array($zres))
		{
			$this->assignments[] = $zrow[4];
			$this->assignmentIds[] = $zrow[0];
			
			if(!in_array($zrow[4],$this->listOfAssignments))
			{
			    $this->listOfAssignments[] = $zrow[4];
			}
			
			if($zrow[1] != '0')
			{
				$que = "SELECT cname, ".getEntityDispName('sno', 'cname', 1)." FROM staffacc_cinfo WHERE type IN ('CUST', 'BOTH') AND sno=".$zrow[1];
				$res=$this->mysqlobj->query($que,$this->db);
				$row=$this->mysqlobj->fetch_row($res);
				$row[0] = stripslashes($row[0]);
				$companyname1=stripslashes($row[1]);
			}
			else
			{
				$companyname1= stripslashes($companyname);
			}
			
			if($zrow[6] == '00/00/0000' || $zrow[6] == '00/00/2000' || $zrow[6] == NULL || $zrow[6] == '')
			{
				$asgnStartDate = "No Start Date";		
			}
			else
			{
				$asgnStartDate = $zrow[6];
			}
									
			if($zrow[7] == '00/00/0000' || $zrow[7] == '00/00/2000' || $zrow[7] == NULL || $zrow[7] == '')
			{
				$asgnEndDate = "No End Date";
			}
			else
			{
				$asgnEndDate = $zrow[7];
			}    
			if($asgnStartDate == "No Start Date" && $asgnEndDate == "No End Date")
			{
				$startEnddate = "";
			}
			else
			{
				$startEnddate = "(".$asgnStartDate." - ".$asgnEndDate.")";
			}
			
			if($zrow[3]=="AS")
			{
				$flg = $this->sel("AS",$zrow[4]);
				$assignOptions.= "<option ".sel("AS",$zrow[4])." id=".$zrow[0]."-".$zrow[1]." value='AS' title='".$companyname1." (Administrative Staff)'>".$companyname1." (Administrative Staff)</option>";
			}
			else if($zrow[3]=="OB")
			{
				$flg = $this->sel("OB",$zrow[4]);
				$assignOptions.= "<option ".$this->sel("OB",$zrow[4])." id=".$zrow[0]."-".$zrow[1]." value='OB' title='".$companyname1." (On Bench)'>".$companyname1." (On Bench)</option>";
			}
			else if($zrow[3]=="OV")
			{
				$flg = $this->sel("OV",$zrow[4]);
				$assignOptions.= "<option ".$this->sel("OV",$zrow[4])." id=".$zrow[0]."-".$zrow[1]." value='OV' title='".$companyname1." (On Vacation)'>".$companyname1." (On Vacation)</option>";
			}
			else
			{
				$lque="SELECT cname, ".getEntityDispName('sno', 'cname', 1)." FROM staffacc_cinfo WHERE type IN ('CUST', 'BOTH') AND  sno=".$zrow[1];
				$lres=$this->mysqlobj->query($lque,$this->db);
				$lrow=$this->mysqlobj->fetch_row($lres);
				$lrow[0] = stripslashes($lrow[0]);
				$clname= stripslashes($lrow[1]);

				if($zrow[4]=="")
					$zrow[4]=" N/A ";

				$flg = $this->sel($zrow[4],$zrow[4]);
				$selAsgnId = '';
				if($asgnid == '')
				{
					$selAsgnId = $this->assignments[0];
				}
				else
				{
					$selAsgnId = $asgnid;
				}
				
				if($ts_type == 'UOM') {//Getting the rates with rate type selected for placeholders
					$asgn_ratetypes = " class='".$zrow[8]."'";
					if ($zrow[10] !='') {
						$shift_name = ' - '.html_tls_specialchars($zrow[10],ENT_QUOTES);
					}else{
						$shift_name = '';
					}
				} else if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == 'Custom') {
					//Getting the rates with rate type selected for placeholders and client location
					$asgn_ratetypes = " class='".$zrow[8]."'";
					$clientlocation = "client_location=".$zrow[1].'-'.$zrow[9]."";
					if ($zrow[10] !='') {
						$shift_name = ' - '.html_tls_specialchars($zrow[10],ENT_QUOTES);
					}else{
						$shift_name = '';
					}
				} else {
					$clientlocation = "";
					$asgn_ratetypes = "";
					if ($zrow[8] !='') {
						$shift_name = ' - '.html_tls_specialchars($zrow[8],ENT_QUOTES);
					}else{
						$shift_name = '';
					}				
				}
				$data_location_state = "";
				if($inout_flag){
					$data_location_state = " data-location_state='".$zrow[9]."'";
				}
				if($clname != '' && $zrow[2] != '')
				{
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes." title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($clname,ENT_QUOTES)." - ".html_tls_specialchars($zrow[2],ENT_QUOTES).$shift_name."' value='".$zrow[4]."' ".$clientlocation.$data_location_state.">".$clname."&nbsp;&nbsp;(".$zrow[4].")&nbsp;-&nbsp;".$zrow[2].$shift_name.$startEnddate."</option>";
				}
				else if($clname != '' && $zrow[2] == '')
				{
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes."  title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($clname,ENT_QUOTES).$shift_name."' value='".$zrow[4]."' ".$clientlocation.$data_location_state.">".$clname."&nbsp;&nbsp;(".$zrow[4].")&nbsp;-&nbsp;".$shift_name.$startEnddate."</option>";					
				}
				else if($clname == '' && $zrow[2] != '')
				{
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes."  title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($zrow[2],ENT_QUOTES).$shift_name."' value='".$zrow[4]."' ".$clientlocation.$data_location_state.">(".$zrow[4].")&nbsp;&nbsp;".$zrow[2]." - ".$shift_name.$startEnddate."</option>";					
				}
				else if($clname == '' && $zrow[2] == '')
				{		
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes."  title='(".$zrow[4].") ".$startEnddate.$shift_name."' value='".$zrow[4]."' ".$clientlocation.$data_location_state.">(".$zrow[4].")&nbsp;-&nbsp;".$shift_name.$startEnddate."</option>";						
				}
			}
		}
		  
		if($module != 'Client') {
		$que="SELECT eartype FROM hrcon_benifit WHERE username='".$employee."' AND ustatus='active'";
		$res=$this->mysqlobj->query($que,$this->db);
		while($data=$this->mysqlobj->fetch_row($res))
		{
			$chk = '';
			if(strpos($asgnid, $data[0]))
			{
			$chk = 'selected';
			}
			$assignOptions.= "<option id='(earn)$data[0]' value='(earn)$data[0]' $chk>$data[0]</option>";
		}
		}
		if($zrowCount < 1)
		{
			$assignOptions.="<option value='0-0' id='0-0'>No Assignment Found</option>";
			
		}

		if($zrowCount > 1){
			$multicss = "multiselect";
		}
		
		if(!empty($tab_index)) { $tab_index = 'tabindex='.$tab_index; } else { $tab_index = '';}

		$onchange	= '';

		if ($inout_flag) {

			$onchange	= 'onchange="javascript:getDataOnAssignment(this.id);"';
		}

		$AssignmentDropdown = '<select '.$onchange.' id="daily_assignemnt_'.$rowid.'" name="daily_assignemnt[0]['.$rowid.']" class="daily_assignemnt select2-select akkenAssgnSelect"'.$tab_index.'>';
		$AssignmentDropdown .= $assignOptions;
		$AssignmentDropdown .= '</select>';
		
		return $AssignmentDropdown;
		
		//return $assignOptions;
    }
    //Added ts_type for Timesheet difference
    function getAssignmentsAjax($employee, $asgnid='', $assignStartDate0, $assignEndDate0, $rowid, $module, $client_id,$ts_type ='')
    {	
		global $companyname, $username;
		$assignOptions = '';
		$asgn_ratetypes = '';
		$shift_name = '';
		
		$assignStartDate = date('Y-m-d', strtotime($assignStartDate0));
		$assignEndDate = date('Y-m-d', strtotime($assignEndDate0));

		$and_clause	= '';

		if ($module == 'Client' && !empty($client_id)) {

			$and_clause	= " AND hrcon_jobs.client IN (".$client_id.")";
			
			//getting contact value of css user
			$sel		= "SELECT t2.con_id, t3.sno FROM staffacc_contact t1 INNER JOIN staffacc_contactacc t2
					    ON t1.sno = t2.con_id INNER JOIN staffacc_cinfo t3 ON t3.username = t1.username WHERE t3.TYPE IN ('CUST', 'BOTH')
					    AND t2.username = ".$username;
			$resselSno	= $this->mysqlobj->query($sel, $this->db);
			$rsselSno	= $this->mysqlobj->fetch_array($resselSno);
			
			$CtVal		= $rsselSno['con_id'];
			$cssUserClient  = $rsselSno['sno'];

			
			$userSelfServicePref 	= $this->getClientPrefs($username);
			$tsPreferencesCSS 	= $userSelfServicePref[7];
			if(strpos($tsPreferencesCSS,"+3+") && strpos($tsPreferencesCSS,"+9+"))
			{
			    $empClientcheckingArr 	= explode(",",$client_id);
			    if(!in_array($cssUserClient, $empClientcheckingArr)) // employee loaded based on assignment billing contact
			    {
				$and_clause	= " AND (hrcon_jobs.client IN (".$client_id.") AND hrcon_jobs.bill_contact= $CtVal) ";
			    }
			    else // same client employee
			    {
				$and_clause	= " AND (hrcon_jobs.client IN (".$client_id.") OR hrcon_jobs.bill_contact= $CtVal) ";
			    }
			}
			else 
			{
			     if((strpos($tsPreferencesCSS,"+4+") || strpos($tsPreferencesCSS,"+5+")) && strpos($tsPreferencesCSS,"+9+"))
			    {
				$and_clause	= " AND (hrcon_jobs.client IN (".$client_id.") OR hrcon_jobs.bill_contact= $CtVal) ";
			    }
			    else
			    if(strpos($tsPreferencesCSS,"+9+"))
			    {
				$and_clause	= " AND (hrcon_jobs.client IN (".$client_id.") AND hrcon_jobs.bill_contact= $CtVal) ";
			    }
			}
			
		}

		if($ts_type == 'UOM' || $ts_type == 'Custom'){//Query for new UOM timesheet getting the rate types with previous query
			 $zque = "SELECT hrcon_jobs.sno, client, project, jtype, pusername,jotype, date_format(str_to_date(s_date,'%m-%d-%Y'),'%m/%d/%Y'), date_format(str_to_date(e_date,'%m-%d-%Y'),'%m/%d/%Y'),multi_rates.mulrates,endclient,shiftname 
			 		FROM hrcon_jobs 
			 		LEFT JOIN shift_setup ON shift_setup.sno = hrcon_jobs.shiftid
					LEFT JOIN (SELECT asgnid,GROUP_CONCAT( CONCAT_WS( '^^', ratemasterid, period ) SEPARATOR '&&' ) AS mulrates FROM `multiplerates_assignment`,hrcon_jobs WHERE ratetype = 'billrate' AND asgn_mode = 'hrcon' AND status = 'Active' AND multiplerates_assignment.asgnid=hrcon_jobs.sno AND hrcon_jobs.username = '".$employee."'  GROUP BY asgnid) multi_rates ON multi_rates.asgnid=hrcon_jobs.sno 
					WHERE username = '".$employee."' AND pusername!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))) AND hrcon_jobs.jtype!='' ".$and_clause." ORDER BY ustatus,pusername";
		} else {
			$zque = "SELECT hrcon_jobs.sno, client, project, jtype, pusername,jotype, date_format(str_to_date(s_date,'%m-%d-%Y'),'%m/%d/%Y'), date_format(str_to_date(e_date,'%m-%d-%Y'),'%m/%d/%Y'),LOWER(sl.state),
			(SELECT shiftname FROM shift_setup WHERE shift_setup.sno = hrcon_jobs.shiftid) AS shiftName
			FROM hrcon_jobs
			LEFT JOIN staffacc_location sl On (hrcon_jobs.endclient = sl.sno)
			WHERE username = '".$employee."' AND pusername!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))) AND hrcon_jobs.jtype!='' ".$and_clause." ORDER BY ustatus,pusername";
		}
		
		$zres=$this->mysqlobj->query($zque,$this->db);
		$zrowCount = mysql_num_rows($zres);
		if($zrowCount == 0){
			if($ts_type == 'UOM' || $ts_type == 'Custom'){
			  $zque = "SELECT hrcon_jobs.sno, client, project, jtype, pusername,jotype, date_format(str_to_date(s_date,'%m-%d-%Y'),'%m/%d/%Y'), date_format(str_to_date(e_date,'%m-%d-%Y'),'%m/%d/%Y'),multi_rates.mulrates,endclient,shift_setup.shiftname FROM hrcon_jobs
			  	LEFT JOIN shift_setup ON shift_setup.sno = hrcon_jobs.shiftid 
				LEFT JOIN (SELECT asgnid,GROUP_CONCAT( CONCAT_WS( '^^', ratemasterid, period ) SEPARATOR '&&' ) AS mulrates FROM `multiplerates_assignment`,hrcon_jobs WHERE ratetype = 'billrate' AND asgn_mode = 'hrcon' AND status = 'Active' AND multiplerates_assignment.asgnid=hrcon_jobs.sno AND hrcon_jobs.username = '".$employee."' GROUP BY asgnid) multi_rates ON multi_rates.asgnid=hrcon_jobs.sno 
				WHERE username = '".$employee."' AND pusername!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND hrcon_jobs.e_date <> hrcon_jobs.s_date AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))) AND hrcon_jobs.jtype!='' ".$and_clause." ORDER BY ustatus,pusername";
			  $zres=$this->mysqlobj->query($zque,$this->db);
			  $zrowCount = mysql_num_rows($zres);
			}
		}
		
		$this->assignmentsajax = array();
		$this->assignmentIdsajax = array();
			
		while($zrow=$this->mysqlobj->fetch_array($zres))
		{
			$this->assignmentsajax[] = $zrow[4];
			$this->assignmentIdsajax[] = $zrow[0];
			
			if($zrow[1] != '0')
			{
				$que = "SELECT cname, ".getEntityDispName('sno', 'cname', 1)." FROM staffacc_cinfo WHERE type IN ('CUST', 'BOTH') AND sno=".$zrow[1];
				$res=$this->mysqlobj->query($que,$this->db);
				$row=$this->mysqlobj->fetch_row($res);
				$row[0] = stripslashes($row[0]);
				$companyname1= stripslashes($row[1]);
			}
			else
			{
				$companyname1= stripslashes($companyname);
			}
			
			if($zrow[6] == '00/00/0000' || $zrow[6] == '00/00/2000' || $zrow[6] == NULL || $zrow[6] == '')
			{
				$asgnStartDate = "No Start Date";		
			}
			else
			{
				$asgnStartDate = $zrow[6];
			}
									
			if($zrow[7] == '00/00/0000' || $zrow[7] == '00/00/2000' || $zrow[7] == NULL || $zrow[7] == '')
			{
				$asgnEndDate = "No End Date";
			}
			else
			{
				$asgnEndDate = $zrow[7];
			}    
			if($asgnStartDate == "No Start Date" && $asgnEndDate == "No End Date")
			{
				$startEnddate = "";
			}
			else
			{
				$startEnddate = "(".$asgnStartDate." - ".$asgnEndDate.")";
			}
			
			if($zrow[3]=="AS")
			{
				$flg = $this->sel("AS",$zrow[4]);
				$assignOptions.= "<option ".sel("AS",$zrow[4])." id=".$zrow[0]."-".$zrow[1]." value='AS' title='".$companyname1." (Administrative Staff)'>".$companyname1." (Administrative Staff)</option>";
			}
			else if($zrow[3]=="OB")
			{
				$flg = $this->sel("OB",$zrow[4]);
				$assignOptions.= "<option ".$this->sel("OB",$zrow[4])." id=".$zrow[0]."-".$zrow[1]." value='OB' title='".$companyname1." (On Bench)'>".$companyname1." (On Bench)</option>";
			}
			else if($zrow[3]=="OV")
			{
				$flg = $this->sel("OV",$zrow[4]);
				$assignOptions.= "<option ".$this->sel("OV",$zrow[4])." id=".$zrow[0]."-".$zrow[1]." value='OV' title='".$companyname1." (On Vacation)'>".$companyname1." (On Vacation)</option>";
			}
			else
			{
				$lque="SELECT cname, ".getEntityDispName('sno', 'cname', 1)." FROM staffacc_cinfo WHERE type IN ('CUST', 'BOTH') AND  sno=".$zrow[1];
				$lres=$this->mysqlobj->query($lque,$this->db);
				$lrow=$this->mysqlobj->fetch_row($lres);
				$clname=$lrow[1];

				if($zrow[4]=="")
					$zrow[4]=" N/A ";

				$flg = $this->sel($zrow[4],$zrow[4]);
				$selAsgnId = '';
				if($asgnid == '')
				{
					$selAsgnId = $this->assignments[0];
				}
				else
				{
					$selAsgnId = $asgnid;
				}
				
				if($ts_type == 'UOM')
				{
					//Getting the rates with rate type selected for placeholders
					$asgn_ratetypes = " class='".$zrow[8]."'";
					$clientlocation = "client_location=".$zrow[1].'-'.$zrow[9]."";
					if ($zrow[10] !='') {
						$shift_name = ' - '.html_tls_specialchars($zrow[10],ENT_QUOTES);
					}else{
						$shift_name = '';
					}	
				} 
				elseif($ts_type == 'Custom') 
				{
					//Getting the rates with rate type selected for placeholders
					$asgn_ratetypes = " class='".$zrow[8]."'";
					$clientlocation = "client_location=".$zrow[1].'-'.$zrow[9]."";	

					if ($zrow[10] !='') {
						$shift_name = ' - '.html_tls_specialchars($zrow[10],ENT_QUOTES);
					}else{
						$shift_name = '';
					}
				}
				else
				{
					if ($zrow[9] !='') {
						$shift_name = ' - '.html_tls_specialchars($zrow[9],ENT_QUOTES);
					}else{
						$shift_name = '';
					}	
				}
				
				$data_location_state = "";
				if($ts_type == "TimeInTimeOut"){
					$data_location_state = " data-location_state='".$zrow[8]."'";
				}
				
				if($clname != '' && $zrow[2] != '')
				{
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes." title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($clname,ENT_QUOTES)." - ".html_tls_specialchars($zrow[2],ENT_QUOTES)."' value='".$zrow[4]."' $clientlocation $data_location_state>".$clname."&nbsp;&nbsp;(".$zrow[4].")&nbsp;-&nbsp;".$zrow[2].$shift_name.$startEnddate."</option>";					
				}
				else if($clname != '' && $zrow[2] == '')
				{
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes."  title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($clname,ENT_QUOTES)."' value='".$zrow[4]."' $clientlocation .$data_location_state>".$clname."&nbsp;&nbsp;(".$zrow[4].") ".$shift_name.$startEnddate."</option>";					
				}
				else if($clname == '' && $zrow[2] != '')
				{
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes."  title='(".$zrow[4].") ".$startEnddate."&nbsp;&nbsp;".html_tls_specialchars($zrow[2],ENT_QUOTES)."' value='".$zrow[4]."' $clientlocation $data_location_state>(".$zrow[4].")&nbsp;&nbsp;".$zrow[2].$shift_name.$startEnddate."</option>";
				}
				else if($clname == '' && $zrow[2] == '')
				{
					$assignOptions.="<option ".$this->sel($selAsgnId,$zrow[4])." id='".$zrow[0]."-".$zrow[1]."' ".$asgn_ratetypes." title='(".$zrow[4].") ".$startEnddate."' value='".$zrow[4]."' $clientlocation $data_location_state>(".$zrow[4].")&nbsp;-&nbsp;".$shift_name.$startEnddate."</option>";		
				}
			}
		}
		if($module != 'Client') {
		$que="SELECT eartype FROM hrcon_benifit WHERE username='".$employee."' AND ustatus='active'";
		$res=$this->mysqlobj->query($que,$this->db);
		while($data=$this->mysqlobj->fetch_row($res))
		{
			$chk = '';
			if(strpos($asgnid, $data[0]))
			{
				$chk = 'selected';
			}
			$assignOptions.= "<option id='(earn)$data[0]' value='(earn)$data[0]' $chk>$data[0]</option>";
		}
		}
		if($zrowCount < 1)
		{
			$assignOptions.="<option value='0-0' id='0-0'>No Assignment Found</option>";
		}
		if(count($this->assignmentsajax) > 1)
		{
		   /*  $multi = '<span class=afontstylee><img src="/PSOS/images/arrow-multiple-16.png" width="12px" height="10px" title="Multiple Assignments"></span>&nbsp;';
			$multcss = 'class="daily_assignemnt afontstylee multiselect"'; */
		}
		else
		{
			$multi = '';
			$multcss = 'class="daily_assignemnt"';
		}
		$AssignmentDropdown = $multi.'<select id="daily_assignemnt_'.$rowid.'" name="daily_assignemnt[0]['.$rowid.']" class="daily_assignemnt select2-select akkenAssgnSelect" >';
		$AssignmentDropdown .= $assignOptions;
		$AssignmentDropdown .= '</select>';
		return $AssignmentDropdown;
    }
    
    function checkAssignmentExists($employee, $assignStartDate0, $assignEndDate0, $module = '', $cval = '')
    {
		$assignStartDate = date('Y-m-d', strtotime($assignStartDate0));
		$assignEndDate = date('Y-m-d', strtotime($assignEndDate0));

		if($module == "Client" && $cval != '')
		{
			$client_cond = " AND client IN (".$cval.")";
		}
			
		$zque = "SELECT sno FROM hrcon_jobs WHERE username = '".$employee."' AND pusername!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))) AND hrcon_jobs.jtype!='' $client_cond ORDER BY udate";
		
		$zres=$this->mysqlobj->query($zque,$this->db);
		$zrowCount = mysql_num_rows($zres);
		if($zrowCount > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
    }
    
    function getClasses($cond='')
    {
		$classes = array();
		$sel="SELECT sno, classname, IFNULL(parent,0) AS parent FROM class_setup WHERE status = 'ACTIVE' ".$cond." ORDER BY classname ASC";
		$ressel=$this->mysqlobj->query($sel,$this->db);
		
		while($myrow=$this->mysqlobj->fetch_array($ressel))
		{
			$classes[] = $myrow;
		}
		return $classes;
    }
    
	//$rangRow .= $this->buildDropDown('daily_dates', $rowid, $assignStartEndDate, $assignStartDate, $script='', $key='', $val='');
    function buildDropDown($name, $rowid, $data, $selected='', $script='', $key='', $val='', $weeklyrange)
    {
		if($name == 'daily_dates')
		{
			$rowid1 = 0;
		}
		else
		{
			$rowid1 = $rowid;
		}
		$options = array();
		if( ($name!='weekly_dates') && ($name!='daily_dates') )
		{
			$options[] = '<option value="">select</option>';
		}
		
		foreach ( $data as $k => $v )
		{
			$varr = explode(" ", $v);
			
			if($key!='' || $val!='')
			{
				$sel = ($v[$key] == $selected)? 'selected' : '';
				$options[] = "<option value='$v[$key]' $sel>$v[$val]</option>";
			}
			else
			{
			
			$sel = ($selected==$varr[0]) ? 'selected' : '';
			
			if(substr_count($v, '-range-') > 0)
			{
				$range = explode("-range-", $v);
				$d1 = date('m/d/Y', strtotime($range[0]));
				$d2 = date('m/d/Y', strtotime($range[1]));
				$v1 = str_replace("-range-", " - ", $v);
				if($weeklyrange=='yes'){
					$options[] = "<option value='$d1-range-$d2' selected='selectd'>$v1</option>";
				}else{
					$options[] = "<option value='$d1-range-$d2' $sel>$v1</option>";
				}
			}
			else
			{
				//$vi = date('Y-m-d', strtotime($v));
				$vi = date('m/d/Y', strtotime($v));
				$options[] = "<option value='$vi' $sel>$v</option>";
			}
			}
		}
		//$dropdown = "<div style='clear:both;>";
		//$dropdown .= "<label class='cf_label' style='width: 100%;'></label>";
		$dropdown .= "<select {$script} class='{$name} afontstylee' id='{$name}_{$rowid}' size='1' name='{$name}[{$rowid1}][{$rowid}]' >";
		$dropdown .= implode("\n", $options);
		$dropdown .= '</select>';
		//$dropdown .= '</div>';
		
		return $dropdown;
    }
    
    function buildDropDownCheck($name, $rowid, $data, $selected='', $script='', $key='', $val='', $weeklyrange, $employee, $inout_flag = false, $tab_index = '', $module = '', $cval = '')
    {
		if($name == 'daily_dates')
		{
			$rowid1 = 0;
		}
		else
		{
			$rowid1 = $rowid;
		}
		$options = array();
		if( ($name!='weekly_dates') && ($name!='daily_dates') )
		{
			$options[] = '<option value="">select</option>';
		}
		
		foreach ( $data as $k => $v )
		{
			$varr = explode(" ", $v);
			
			if($key!='' || $val!='')
			{
				
			$sel = ($v[$key] == $selected)? 'selected' : '';
			$options[] = "<option value='$v[$key]' $sel>$v[$val]</option>";
				
			}
			else
			{
				$sel = ($selected==$varr[0]) ? 'selected' : '';

				if ($inout_flag) {

					$vi	= date('m/d/Y', strtotime($v));

					if ($this->checkAssignmentExists($employee, $v, $v, $module, $cval)) {

						$options[] = "<option value='$vi' $sel>$v</option>";
					}

				} else {

					if (substr_count($v, '-range-') > 0) {

						$range	= explode("-range-", $v);
						$d1		= date('m/d/Y', strtotime($range[0]));
						$d2		= date('m/d/Y', strtotime($range[1]));
						$v1		= str_replace("-range-", " - ", $v);

						if ($weeklyrange=='yes') {

							if ($this->checkAssignmentExists($employee, $d1, $d2, $module, $cval)) {

								$options[] = "<option value='$d1-range-$d2' selected='selectd'>$v1</option>";
							}

						} else {

							if ($this->checkAssignmentExists($employee, $d1, $d2, $module, $cval)) {

								$options[] = "<option value='$d1-range-$d2' $sel>$v1</option>";
							}
						}

					} else {

						$vi	= date('m/d/Y', strtotime($v));

						if ($this->checkAssignmentExists($employee, $v, $v, $module, $cval)) {

							$options[] = "<option value='$vi' $sel>$v</option>";
						}
					}
				}
			}
		}
		//$dropdown = "<div style='clear:both;>";
		//$dropdown .= "<label class='cf_label' style='width: 100%;'></label>";
		$dropdown .= "<select {$script} class='{$name}  select2-select akkenDateSelectWid' id='{$name}_{$rowid}' size='1' name='{$name}[{$rowid1}][{$rowid}]' tabindex='".$tab_index++."'>";
		$dropdown .= implode("\n", $options);
		$dropdown .= '</select>';
		//$dropdown .= '</div>';
		
		return $dropdown;
    }
    
    function buildDropDownClasses($name, $rowid, $data, $selected='', $script='', $key='', $val='', $weeklyrange, $tab_index='')
    {
		if($name == 'daily_dates')
		{
			$rowid1 = 0;
		}
		else
		{
			$rowid1 = $rowid;
		}
		$options = array();
		if( ($name!='weekly_dates') && ($name!='daily_dates') )
		{
			$options[] = '<option  value="0">--Select--</option>';
		}
		
		foreach ( $data as $k => $v )
		{
			$varr = explode(" ", $v);
			
			if($key!='' || $val!='')
			{
				$sel = ($v[$key] == $selected)? 'selected' : '';
				$options[] = "<option value='$v[$key]' $sel>$v[$val]</option>";
			}
			else
			{
			
			$sel = ($selected==$varr[0]) ? 'selected' : '';
			
			if(substr_count($v, '-range-') > 0)
			{
				$range = explode("-range-", $v);
				$d1 = date('m/d/Y', strtotime($range[0]));
				$d2 = date('m/d/Y', strtotime($range[1]));
				$v1 = str_replace("-range-", " - ", $v);
				if($weeklyrange=='yes'){
					$options[] = "<option value='$d1-range-$d2' selected='selectd'>$v1</option>";
				}else{
					$options[] = "<option value='$d1-range-$d2' $sel>$v1</option>";
				}
			}
			else
			{
				//$vi = date('Y-m-d', strtotime($v));
				$vi = date('m/d/Y', strtotime($v));
				$options[] = "<option value='$vi' $sel>$v</option>";
			}
			}
		}
		//$dropdown = "<div style='clear:both;>";
		//$dropdown .= "<label class='cf_label' style='width: 150px;'></label>";
		$dropdown .= "<select {$script} class='{$name} select2-select akkenClassSelect' id='{$name}_{$rowid}' size='1' name='{$name}[{$rowid1}]' tabindex='".$tab_index."'>";
		$dropdown .= implode("\n", $options);
		$dropdown .= '</select>';
		//$dropdown .= '</div>';
		
		return $dropdown;
    }
    
	function buildDatesdropdown($timesheet_date_arr, $timesheet_start_date, $timesheet_end_date, $inout_flag = false, $range="no")
	{
		if ($inout_flag) {

			return $timesheet_date_arr;

		} else {

			if($range == "yes"){
				$timesheet_date_arr = array();
				$summary_dates = $timesheet_start_date."-range-".$timesheet_end_date;
				array_push($timesheet_date_arr, $summary_dates);
			}
		}

		return $timesheet_date_arr;
	}
    
    function getRateTypes($asignid='')
    {
		$ratetypes = array();
		$select_que="SELECT sno,rateid,name,status,default_status from multiplerates_master where rateid !='rate4' and status = 'Active' order by sno";
		$ressel=$this->mysqlobj->query($select_que,$this->db);
		
		while($myrow=$this->mysqlobj->fetch_array($ressel))
		{
			$ratetypes[] = $myrow;			
		}
		return $ratetypes;
    }
    
    function getRateTypesWithPayNBill($asignid, $rates='', $rowid, $parid, $mode='', $req_str='', $type='', $disableFlag='')
    {
		$req_bill_arr = explode(',',$req_str[4]);
		$req_rate_arr = explode(',',$req_str[5]);
		
		$rateHourArr = array();
		
		if($rates == '')
		{
			$req_bill_arr = explode(',',$req_str[4]);
			$req_rate_arr = explode(',',$req_str[5]);
		}
		else
		{
			$ratesArr = explode(",", $rates);
			foreach($ratesArr as $val)
			{
				$valArr = explode("|", $val);
				if($valArr[0] == '')
				{
					$rate = 'rate1';
				}
				else
				{
					$rate = $valArr[0];
				}
				$rateHourArr[$rate] = $valArr[1];
				$billArr[$rate] = $valArr[2];	
			}
		}
		 $select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype, manage.name  FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno  INNER JOIN manage ON manage.sno=t2.jotype where ratemasterid != 'rate4' AND asgnid = '".$asignid."'  AND t1.asgn_mode = 'hrcon' ORDER BY t1.sno";

		$result_ratemaster=$this->mysqlobj->query($select_ratemaster,$this->db);
		$query_count = mysql_num_rows($result_ratemaster);
	
		while($row_ratemaster=$this->mysqlobj->fetch_array($result_ratemaster))
		{
			if($row_ratemaster['name'] == 'Internal Direct' && $row_ratemaster['ratemasterid'] == 'rate1'){
				$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = '0.00';
			}else{
				$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = $row_ratemaster['rate'];
			}
			$jtype = $row_ratemaster['jtype'];
		}
		
		$ratetypes = array();
		$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND ratetype='billrate' AND asgn_mode = 'hrcon') = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon') AS billable,(SELECT t4.period FROM multiplerates_assignment t4 WHERE t4.asgnid = '".$asignid."' AND t4.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon') AS period  FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' and status = 'Active' ORDER BY t1.sno";
	
	 
		$ressel=$this->mysqlobj->query($select_que,$this->db);
		$rowcount = mysql_num_rows($ressel);
		
		$r = 0;
		$ratetype = '';

		while($myrow=$this->mysqlobj->fetch_array($ressel))
		{
			$pay = ($rateArr[$myrow['rateid']]['payrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['payrate'];
			$bill = ($rateArr[$myrow['rateid']]['billrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['billrate'];
			
			
			$ratetype .= '<td  valign="top" class="afontstylee tsrates" align="left">';
			
			if($mode!='' && $req_str!='')
			{
				if($req_str[4] != '')
				{
					$hidbillchk = $req_bill_arr[$r];
				}
				else
				{
					$hidbillchk = $myrow['billable'];
				}
				if($asignid == ''){
					$myrow['required'] = 'Y';
					$myrow['billable'] = 'N';
				
				}
				$xyz = ($rateHourArr[$myrow['rateid']] != '')?$rateHourArr[$myrow['rateid']]:$req_rate_arr[$r];

				$ratetype .= '<input '.$disableFlag.' style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;" type="text" value="'.$xyz.'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.' '.$myrow['rateid'].' rates " name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalcMuti(\'timesheetRate'.$r.'\', '.$rowid.', '.$rowcount.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).'>';
				$checkBoxenable = $this->chk($hidbillchk);
				
				if($jtype == 'OP')
				{
					$ratetype .='<label class="container-chk">';
					$ratetype .= '<input '.$disableFlag.' style="margin-top:0px;vertical-align:top" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Y" '.$checkBoxenable.' '.$this->disable($myrow['required']).'>';
					$ratetype .='<span class="checkmark"></span>';
					$ratetype .='</label>';
				}
				else
				{
					$ratetype .='<label class="container-chk">';
					$ratetype .= '<input style="margin-top:0px;vertical-align:top" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Y" '.$this->disable($myrow['required']).'>';
					$ratetype .='<span class="checkmark"></span>';
					$ratetype .='</label>';
				}		    
				
			}
			else
			{
				$ratetype .= '<input style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;" type="text" value="'.$rateHourArr[$myrow['rateid']].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.'" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalc(\'timesheetRate'.$r.'\', this.id, '.$rowcount.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).'>';
				if($jtype == 'OP')
				{
					if(count($billArr) > 0)
					{
						$checkBoxenable = $this->chk(substr($billArr[$myrow['rateid']], 0, 1));
						$ratetype .='<label class="container-chk">';
						$ratetype .= '<input style="margin-top:0px;vertical-align:top" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Y" '.$checkBoxenable.' '.$this->disable($myrow['required']).'>';
						$ratetype .='<span class="checkmark"></span>';
						$ratetype .='</label>';
					}
					else
					{
						$checkBoxenable = $this->chk($myrow['billable']);
						
						$ratetype .='<label class="container-chk">';
						$ratetype .= '<input style="margin-top:0px;vertical-align:top" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Y" '.$checkBoxenable.' '.$this->disable($myrow['required']).'>';
						$ratetype .='<span class="checkmark"></span>';
						$ratetype .='</label>';
					}
				}
				else
				{
					$ratetype .= '<input style="margin-top:0px;vertical-align:top" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Y" disabled>';
				}
			
			}

			if($type != 'single')
			{
				if(SHOWPAYANDBILL == 'Y' && ($_SESSION['sess_usertype'] == 'UL' || $_SESSION['sess_usertype'] == 'BO'))
				{
					$ratetype .= "<br />P <span id='daily_rate_pay_".$r."_".$rowid."' name='daily_rate_pay_".$r."_".$rowid."'>".$pay."</span> <br />B <span id='daily_rate_bill_".$r."_".$rowid."' name='daily_rate_bill_".$r."_".$rowid."'>".$bill."</span><span class='daily_rate_pay_link_".$rowid."'>";
					if($asignid != '')
					{
						$ratetype .= $this->getAssignEditLink($asignid, $rowid);
					}
				}
			}
			
			$ratetype .= '</span></td>';
			$r++;
		}
		return $ratetype;
    }
    
    function getAssignEditLink($hrsno, $rowid)
    {
		$que12 = "select contactsno,appno from assignment_schedule where contactsno like '%".$hrsno."|%' AND modulename='HR->Assignments' AND invapproved='active'";
		$res12 = mysql_query($que12,$this->db);
		$row12 = mysql_fetch_row($res12);
		$aid = explode("|",$row12[0]);
			
		$ratetype ="<font class=afontstyle>&nbsp;&nbsp;<a class=\"class_for_ref\" href=\"javascript:doEditAssign('".$hrsno."','".$aid[1]."','".$row12[1]."', '".$rowid."');\"><img src='/PSOS/images/assignments10x10.png' border='0'></a></font>";
		
		return $ratetype;
    }
    
    function getAssignEditLinkAjax($hrsno, $rowid)
    {
		$que12 = "select contactsno,appno from assignment_schedule where contactsno like '%".$hrsno."|%' AND modulename='HR->Assignments' AND invapproved='active'";
		$res12 = mysql_query($que12,$this->db);
		$row12 = mysql_fetch_row($res12);
		$aid = explode("|",$row12[0]);
			
		$ratetype ="<font class=afontstyle>&nbsp;&nbsp;<a class=\"class_for_ref\" href=\"javascript:doEditAssign('".$hrsno."','".$aid[1]."','".$row12[1]."', '".$rowid."');\"><img src='/PSOS/images/assignments10x10.png' border='0'></a></font>";
		
		return $ratetype;
    }
     function getRateTypesWithPayNBillSingle_CUSTOM($asignid, $rates='', $rowid, $parid, $mode='', $req_str='', $type='', $ratesAvail, $ts_type='', $notes='', $tssno = '',$module='',$rateids_arr=''){
    
	$req_bill_arr = explode(',',$req_str[4]);
	$req_rate_arr = explode(',',$req_str[5]);	
	$tssnoArr = explode(',',$tssno);
	$t = 0;
	$uom_query ='';
	$rateHourArr = array();

		if($rates != '')
		{
			$ratesArr = explode(",", $rates);
			foreach($ratesArr as $val)
			{
				$valArr = explode("|", $val);
				if($valArr[0] == '')
				{
					$rate = 'rate1';
				}
				else
				{
					$rate = $valArr[0];
				}
				
				$rateHourArr[$rate] = $valArr[1];
				$billArr[$rate] = $valArr[2];
				$notestatus[$rate] = '';
				$notessno[$rate] ='';
				
				if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == 'Custom'){
					$notestatus[$rate] = $valArr[3];
					$notetssno[$rate] = $valArr[5];
				}
		
			}
		}
		
		$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND t1.asgn_mode = 'hrcon' AND t1.ratetype = 'billrate' AND t1.rate!='' AND ( (t1.rate>0 AND t1.ratemasterid IN ('rate2','rate3')) OR  (t1.rate>1 AND t1.ratemasterid IN ('rate1')) OR (t1.rate>0 AND t1.ratemasterid NOT IN ('rate1','rate2','rate3'))) ORDER BY t1.sno";
		$result_ratemaster=$this->mysqlobj->query($select_ratemaster,$this->db);
		$query_count = mysql_num_rows($result_ratemaster);
		
		while($row_ratemaster=$this->mysqlobj->fetch_array($result_ratemaster))
		{
			$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = $row_ratemaster['rate'];
			$jtype = $row_ratemaster['jtype'];
		}
		
		$ratetypes = array();
		
		
		
		$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND  t2.ratetype='billrate' AND t2.asgn_mode = 'hrcon'  AND t2.rate!='' AND ( (t2.rate>0 AND t2.ratemasterid IN ('rate2','rate3')) OR  (t2.rate>1 AND t2.ratemasterid IN ('rate1')) OR (t2.rate>0 AND t2.ratemasterid NOT IN ('rate1','rate2','rate3')))) = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' AND t3.rate!='' AND ( (t3.rate>0 AND t3.ratemasterid IN ('rate2','rate3')) OR  (t3.rate>1 AND t3.ratemasterid IN ('rate1')) OR (t3.rate>0 AND t3.ratemasterid NOT IN ('rate1','rate2','rate3')))) AS billable,t4.period FROM multiplerates_master t1 LEFT JOIN multiplerates_assignment t4 ON (t4.ratemasterid = t1.rateid AND t4.asgnid = '".$asignid."' AND t4.ratetype='billrate' AND t4.asgn_mode = 'hrcon' AND t4.rate!='') WHERE t1.rateid != 'rate4' and t1.status = 'Active' ORDER BY t1.sno";
		
		$ressel=$this->mysqlobj->query($select_que,$this->db);
		$rowcount = mysql_num_rows($ressel);
		
		$r = 0;
		$ratetype = '';
		$tempHoursVal = 0;
		$tempDayVal = 0;
		$tempMileVal = 0;
		$tempUnitVal = 0;
		while($myrow=$this->mysqlobj->fetch_array($ressel))
		{
			$hiddenBillable[$myrow['rateid']] = ($myrow['billable'] == '')?'N':$myrow['billable'];
			if(in_array($myrow['rateid'], $ratesAvail))
			{  
				if ($myrow['period'] !=NULL) {
					$myrowPeriod = $this->getTooltip($myrow['period']);
					if (array_key_exists($r,$this->eachrowidTotalValArr)) {
						
						if (!empty($rateHourArr[$myrow['rateid']]) && $rateHourArr[$myrow['rateid']] !="0.00") {
							
							if (array_key_exists($myrowPeriod,$this->eachrowidTotalValArr[$r])) {

								array_push($this->eachrowidTotalValArr[$r][$myrowPeriod], $rateHourArr[$myrow['rateid']]);
							}else{

								if (!empty($rateHourArr[$myrow['rateid']]) && $rateHourArr[$myrow['rateid']] !="0.00") 
								{
									$this->eachrowidTotalValArr[$r][$myrowPeriod] = array();
									array_push($this->eachrowidTotalValArr[$r][$myrowPeriod], $rateHourArr[$myrow['rateid']]);
								}
							}
						}
					}else{
						
						if (!empty($rateHourArr[$myrow['rateid']]) && $rateHourArr[$myrow['rateid']] !="0.00") {
							$this->eachrowidTotalValArr[$r] = array();
							$this->eachrowidTotalValArr[$r][$myrowPeriod] = array();
							array_push($this->eachrowidTotalValArr[$r][$myrowPeriod], $rateHourArr[$myrow['rateid']]);
						}
					}
				}
				$pay = ($rateArr[$myrow['rateid']]['payrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['payrate'];
				$bill = ($rateArr[$myrow['rateid']]['billrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['billrate'];
				$ratetype .= '<td valign="top" class="afontstylee tsrates" align="left">';
				
				if($mode!='' && $req_str!='')
				{
					if($req_str[4] != '')
					{
						$hidbillchk = $req_bill_arr[$r];
					}
					else
					{
						$hidbillchk = $myrow['billable'];
					}
					$tssnoattr =  '';
					$tssnostr = '';
					if($rateHourArr[$myrow['rateid']] != ''){
						$tssnoattr =  ' tssno="'.$notetssno[$myrow['rateid']].'"';
						$tssnostr = $notetssno[$myrow['rateid']];
					}
					$cust_placeholders = "";
					$cust_title = "";
					$cust_rate_uom = "";
					if ($this->disable($myrow['required']) != "disabled") {
						$cust_placeholders = $myrowPeriod;
						$cust_title = $myrowPeriod;
						$cust_rate_uom = $myrowPeriod;
						$tempRateVal = $rateHourArr[$myrow['rateid']];
						switch($cust_placeholders){
						                
						        case 'Days':
						                $tempDayVal = $tempDayVal+$tempRateVal;		
						                break;
						        case 'Day':
					                $tempDayVal = $tempDayVal+$tempRateVal;		
					                break;
						        case 'Miles':
						                $tempMileVal = $tempMileVal+$tempRateVal;		
						                break;
						        case 'Units':
						                $tempUnitVal = $tempUnitVal+$tempRateVal;		
						                break;
						        default: 
						                $tempHoursVal = $tempHoursVal+$tempRateVal;				
						}
					}	
					$ratetype .= '<input style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="text" value="'.$req_rate_arr[$r].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.'" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeypress="return blockNonNumbers(this, event, true, false);" onkeyup="formatDecNum(this);TimesheetCalcMuti(\'timesheetRate'.$r.'\', '.$rowid.', '.$rowcount.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).' ratename="'.$myrow['rateid'].'" '.$tssnoattr.' placeholder="'.$cust_placeholders.'" title="'.$cust_placeholders.'" rate_uom="'.$cust_placeholders.'" >';
					
					$ratetype .= '<label class="container-chk" style="'.$this->displayInputField($myrow['required']).'">';
					$ratetype .= '<input style="margin-top:0px;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk($hidbillchk).' '.$this->disable($myrow['required']).'>';
					$ratetype .= '<span class="checkmark"></span>';
					$ratetype .= '</label>';
				}
				else {
					$tssnoattr =  '';
					$tssnostr ='';
					if($rateHourArr[$myrow['rateid']] != ''){
						$tssnoattr =  ' tssno="'.$notetssno[$myrow['rateid']].'"';
						$tssnostr = $notetssno[$myrow['rateid']];
					}
					$cust_placeholders = "";
					$cust_title = "";
					$cust_rate_uom = "";
					if ($this->disable($myrow['required']) != "disabled") {
						$cust_placeholders = $myrowPeriod;
						$cust_title = $myrowPeriod;
						$cust_rate_uom = $myrowPeriod;
					}	
					$tempRateVal = $rateHourArr[$myrow['rateid']];
					switch($cust_placeholders){
						                
					        case 'Days':
					                $tempDayVal = $tempDayVal+$tempRateVal;		
					                break;
					        case 'Day':
					                $tempDayVal = $tempDayVal+$tempRateVal;		
					                break;
					        case 'Miles':
					                $tempMileVal = $tempMileVal+$tempRateVal;		
					                break;
					        case 'Units':
					                $tempUnitVal = $tempUnitVal+$tempRateVal;		
					                break;
					        default: 
					                $tempHoursVal = $tempHoursVal+$tempRateVal;				
					}
					$ratetype .= '<input  style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="text" value="'.$rateHourArr[$myrow['rateid']].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.' '.$myrow['rateid'].' rates" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeypress="return blockNonNumbers(this, event, true, false);" onkeyup="formatDecNum(this);TimesheetCalc(\'timesheetRate'.$r.'\', this.id, '.$this->rateTypeCountSingle.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).' ratename="'.$myrow['rateid'].'" '.$tssnoattr.' placeholder="'.$cust_placeholders.'" title="'.$cust_placeholders.'" rate_uom="'.$cust_placeholders.'" >';

				
					if(count($billArr) > 0)
					{
						if($module == 'Client' || $module == 'MyProfile'){
							$styles = 'style="margin-top:0px;vertical-align:top;display:none;"';
							$chk_styles = 'style="display:none;"';
						}else{
							$styles = 'style="margin-top:0px;vertical-align:top;'.$this->displayInputField($myrow['required']).'"';
							$chk_styles = 'style="'.$this->displayInputField($myrow['required']).'"';
						}
						$ratetype .= '<label class="container-chk" '.$chk_styles.'>';
						$ratetype .= '<input '.$styles.' type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk(substr($billArr[$myrow['rateid']], 0, 1)).' >';
						$ratetype .= '<span class="checkmark"></span>';
						$ratetype .= '</label>';
					}
					else
					{
						if($module == 'Client' || $module == 'MyProfile'){
							$styles = 'style="margin-top:0px;vertical-align:top;display:none;"';
							$chk_styles = 'style="display:none;"';
						}else{
							$styles = 'style="margin-top:0px;vertical-align:top;'.$this->displayInputField($myrow['required']).'"';
							$chk_styles = 'style="'.$this->displayInputField($myrow['required']).'"';
						}
						$ratetype .= '<label class="container-chk" '.$chk_styles.'>';					
						$ratetype .= '<input '.$styles.' type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk($myrow['billable']).'>';
						$ratetype .= '<span class="checkmark"></span>';
						$ratetype .= '</label>';
					}

					if(THERAPY_SOURCE_ENABLED =='Y' && $ts_type =='Custom') {

						$person_attach_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachmentsCountDetails']['parid_'.$parid]['tsrowid_'.$rowid];
						$person_attach = '';
						if (count($person_attach_arry)>0) {
							if (!empty($person_attach_arry['tssno_'.$tssnostr])) {
								$person_attach = $person_attach_arry['tssno_'.$tssnostr].'|Y';
							}
						}
						
						$person_notes_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachedNoteDetails']['parid_'.$parid]['tsrowid_'.$rowid];
						$person_notes = '';
						if (count($person_notes_arry)>0) {
							if (!empty($person_notes_arry['tssno_'.$tssnostr])) {
								$person_notes = $person_notes_arry['tssno_'.$tssnostr];
							}
						}
						
						//if($notestatus[$myrow['rateid']]=='Y') {						
							
							if(!empty($person_notes) || !empty($person_attach)){
								$this->gettsnotesDetails($notetssno[$myrow['rateid']], $r, $rowid);
							
								$ratetype .= '<div class="marginNote"><span id="person_rate_note_'.$r.'_'.$rowid.'" href="#" onclick="if(document.getElementById(\'daily_rate_'.$r.'_'.$rowid.'\').disabled==false){addRateNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'row_'.$r.'_'.$rowid.'\',document.getElementById(\'daily_dates_'.$rowid.'\').value,0);}" class="notesclass" style="'.$this->displayInputField($myrow['required']).'"><i class="fa fa-edit fa-lg"></i>Edit Notes</span></div>';
								
								$valArr='';
								
							}else {								
								$ratetype .= '<div class="marginNote"><span id="person_rate_note_'.$r.'_'.$rowid.'" href="#" onclick="if(document.getElementById(\'daily_rate_'.$r.'_'.$rowid.'\').disabled==false){addRateNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'row_'.$r.'_'.$rowid.'\',document.getElementById(\'daily_dates_'.$rowid.'\').value,0);}" class="notesclass" style="'.$this->displayInputField($myrow['required']).'"><i class="fa fa-file-o fa-lg"></i>Add Notes</span></div>';								
							}
						/*}
						else {								
								$ratetype .= '<div class="marginNote"><span id="person_rate_note_'.$r.'_'.$rowid.'" href="#" onclick="if(document.getElementById(\'daily_rate_'.$r.'_'.$rowid.'\').disabled==false){addRateNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'row_'.$r.'_'.$rowid.'\',document.getElementById(\'daily_dates_'.$rowid.'\').value,0);}" class="notesclass" style="'.$this->displayInputField($myrow['required']).'"><i class="fa fa-file-o fa-lg"></i>Add Notes</span></div>';								
						}*/
					}
					if($rateHourArr[$myrow['rateid']] != ''){
						$t++;
					}
				}    
				if($type != 'single') {
					if(SHOWPAYANDBILL == 'Y' && ($_SESSION['sess_usertype'] == 'UL' || $_SESSION['sess_usertype'] == 'BO'))
					{
						$ratetype .= "<br />P <span id='daily_rate_pay_".$r."_".$rowid."' name='daily_rate_pay_".$r."_".$rowid."'>".$pay."</span> <br />B <span id='daily_rate_bill_".$r."_".$rowid."' name='daily_rate_bill_".$r."_".$rowid."'>".$bill."<span>";
					}
				}				
				$ratetype .= '</td>';
				$notestatus[$myrow['rateid']]	= '';
				$notessno[$myrow['rateid']]	= '';
				$r++;
			}else{
				if(!empty($rateids_arr) && in_array($myrow['rateid'], $rateids_arr)){
					$ratetype .= '<td></td>';
					$r++;
				}
				
			}
		}		
		//if($ts_type == 'Custom'){//For Custom Timesheet input hidden fields for totals
		$tuom = "<input type='hidden' name='totaluomdays_".$rowid."' id='totaluomdays_".$rowid."' value='".$tempDayVal."' class='totaluomdays_cls'><input type='hidden' name='totaluommiles_".$rowid."' id='totaluommiles_".$rowid."' value='".$tempMileVal."'  class='totaluommiles_cls'><input type='hidden' name='totaluomunits_".$rowid."' id='totaluomunits_".$rowid."' value='".$tempUnitVal."' class='totaluomunits_cls'>
				<input type='text' name='daystotalDiv_".$rowid."' id='daystotalDiv_".$rowid."' value='".$tempDayVal."' style='display:none;'><input type='text' name='milestotalDiv_".$rowid."' id='milestotalDiv_".$rowid."' value='".$tempMileVal."' style='display:none;'><input type='text' name='unitstotalDiv_".$rowid."' id='unitstotalDiv_".$rowid."' value='".$tempUnitVal."' style='display:none;'>";
		//}
		$ratetype .= "<td valign='top' class='afontstylee' width='3%'><input type='hidden' name='daytotalhrs_".$rowid."' id='daytotalhrs_".$rowid."' value='".$tempHoursVal."' class='daytotalhrs_cls'>".$tuom."<input type='hidden' name='editrow[]' id='editrow_".$rowid."' value='".$rowid."' ><input type='text' name='daytotalhrsDiv_".$rowid."' id='daytotalhrsDiv_".$rowid."' value='".$tempHoursVal."' style='display:none;'></td>";
		$this->hiddenBillable[] = $hiddenBillable;			
		return $ratetype;
    }
    function getRateTypesWithPayNBillSingle($asignid, $rates='', $rowid, $parid, $mode='', $req_str='', $type='', $ratesAvail, $ts_type='', $notes='', $tssno = '',$module='',$rateids_arr=''){
    
	$req_bill_arr = explode(',',$req_str[4]);
	$req_rate_arr = explode(',',$req_str[5]);	
	$tssnoArr = explode(',',$tssno);
	$t = 0;
	$rateHourArr = array();

		if($rates != '')
		{
			$ratesArr = explode(",", $rates);
			foreach($ratesArr as $val)
			{
				$valArr = explode("|", $val);
				if($valArr[0] == '')
				{
					$rate = 'rate1';
				}
				else
				{
					$rate = $valArr[0];
				}
				
				$rateHourArr[$rate] = $valArr[1];
				$billArr[$rate] = $valArr[2];
				$notestatus[$rate] = '';
				$notessno[$rate] ='';
				
				if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == 'Custom'){
					$notestatus[$rate] = $valArr[3];
					$notetssno[$rate] = $valArr[5];
				}
		
			}
		}
		$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND period =  'HOUR' AND t1.asgn_mode = 'hrcon' ORDER BY t1.sno";

		$result_ratemaster=$this->mysqlobj->query($select_ratemaster,$this->db);
		
		while($row_ratemaster=$this->mysqlobj->fetch_array($result_ratemaster))
		{
			$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = $row_ratemaster['rate'];
			$jtype = $row_ratemaster['jtype'];
		}
		
		$ratetypes = array();
		$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND t2.asgn_mode = 'hrcon') = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon') AS billable FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' and status = 'Active' ORDER BY t1.sno";
		
		$ressel=$this->mysqlobj->query($select_que,$this->db);
		$rowcount = mysql_num_rows($ressel);
		
		$r = 0;
		$ratetype = '';
		while($myrow=$this->mysqlobj->fetch_array($ressel))
		{
			$hiddenBillable[$myrow['rateid']] = ($myrow['billable'] == '')?'N':$myrow['billable'];
			if(in_array($myrow['rateid'], $ratesAvail))
			{  
				$pay = ($rateArr[$myrow['rateid']]['payrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['payrate'];
				$bill = ($rateArr[$myrow['rateid']]['billrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['billrate'];
				$ratetype .= '<td valign="top" class="afontstylee tsrates" align="left">';
				
				if($mode!='' && $req_str!='')
				{
					if($req_str[4] != '')
					{
						$hidbillchk = $req_bill_arr[$r];
					}
					else
					{
						$hidbillchk = $myrow['billable'];
					}
					if($myrow['rateid'] == 'rate1' || $myrow['rateid'] == 'rate2' || $myrow['rateid'] == 'rate3')
					{
						$ratetype .= '<input style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;" type="text" value="'.$req_rate_arr[$r].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.'" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalcMuti(\'timesheetRate'.$r.'\', '.$rowid.', '.$rowcount.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" >';				
					}
					else
					{
						$ratetype .= '<input style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;" type="text" value="'.$req_rate_arr[$r].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.'" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalcMuti(\'timesheetRate'.$r.'\', '.$rowid.', '.$rowcount.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).'>';
					}
						$ratetype .= '<input style="margin-top:0px;vertical-align:top" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk($hidbillchk).' '.$this->disable($myrow['required']).'>';    	
				}
				else {
					$tssnoattr =  '';
					if($rateHourArr[$myrow['rateid']] != ''){
						$tssnoattr =  ' tssno="'.$tssnoArr[$t].'"';
					}
					if($myrow['rateid'] == 'rate1' || $myrow['rateid'] == 'rate2' || $myrow['rateid'] == 'rate3') {
						$ratetype .= '<input style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;" type="text" value="'.$rateHourArr[$myrow['rateid']].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.' '.$myrow['rateid'].' rates" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalc(\'timesheetRate'.$r.'\', this.id, '.$this->rateTypeCountSingle.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" ratename="'.$myrow['rateid'].'" '.$tssnoattr.'>';

					}
					else
					{
						$ratetype .= '<input  style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;" type="text" value="'.$rateHourArr[$myrow['rateid']].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.' '.$myrow['rateid'].' rates" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalc(\'timesheetRate'.$r.'\', this.id, '.$this->rateTypeCountSingle.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).' ratename="'.$myrow['rateid'].'" '.$tssnoattr.'>';
					}
					if(count($billArr) > 0)
					{
						if($module == 'Client' || $module == 'MyProfile'){
							$styles = 'style="margin-top:0px;vertical-align:top;display:none;"';
						}else{
							$styles = 'style="margin-top:0px;vertical-align:top;"';
						}
						$ratetype .= '<label class="container-chk">';
						$ratetype .= '<input '.$styles.' type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk(substr($billArr[$myrow['rateid']], 0, 1)).' >';
						$ratetype .= '<span class="checkmark"></span>';
						$ratetype .= '</label>';
					}
					else
					{
						if($module == 'Client' || $module == 'MyProfile'){
							$styles = 'style="margin-top:0px;vertical-align:top;display:none;"';
						}else{
							$styles = 'style="margin-top:0px;vertical-align:top;"';
						}
						$ratetype .= '<label class="container-chk">';
						$ratetype .= '<input '.$styles.' type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk($myrow['billable']).'>';
						$ratetype .= '<span class="checkmark"></span>';
						$ratetype .= '</label>';
					}
					if(THERAPY_SOURCE_ENABLED =='Y' && $ts_type =='Custom') {
						if($notestatus[$myrow['rateid']]=='Y') {						
							$this->gettsnotesDetails($notetssno[$myrow['rateid']], $r, $rowid);
								
							if(isset($_SESSION['AddCustomTimeSheetNotes']['row_'.$r.'_'.$rowid.''])){
								$ratetype .= '<br><span id="person_rate_note_'.$r.'_'.$rowid.'" href="#" onclick="if(document.getElementById(\'daily_rate_'.$r.'_'.$rowid.'\').disabled==false){addRateNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'row_'.$r.'_'.$rowid.'\',document.getElementById(\'daily_dates_'.$rowid.'\').value,0);}" class="notesclass">Edit Notes</span>';
								
								$valArr='';
								
							}else {								
								$ratetype .= '<br><span id="person_rate_note_'.$r.'_'.$rowid.'" href="#" onclick="if(document.getElementById(\'daily_rate_'.$r.'_'.$rowid.'\').disabled==false){addRateNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'row_'.$r.'_'.$rowid.'\',document.getElementById(\'daily_dates_'.$rowid.'\').value,0);}" class="notesclass">Add Notes</span>';								
							}
						}
						else {								
								$ratetype .= '<br><span id="person_rate_note_'.$r.'_'.$rowid.'" href="#" onclick="if(document.getElementById(\'daily_rate_'.$r.'_'.$rowid.'\').disabled==false){addRateNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'row_'.$r.'_'.$rowid.'\',document.getElementById(\'daily_dates_'.$rowid.'\').value,0);}" class="notesclass">Add Notes</span>';								
						}
					}
					if($rateHourArr[$myrow['rateid']] != ''){
						$t++;
					}
				}    
				if($type != 'single') {
					if(SHOWPAYANDBILL == 'Y' && ($_SESSION['sess_usertype'] == 'UL' || $_SESSION['sess_usertype'] == 'BO'))
					{
						$ratetype .= "<br />P <span id='daily_rate_pay_".$r."_".$rowid."' name='daily_rate_pay_".$r."_".$rowid."'>".$pay."</span> <br />B <span id='daily_rate_bill_".$r."_".$rowid."' name='daily_rate_bill_".$r."_".$rowid."'>".$bill."<span>";
					}
				}				
				$ratetype .= '</td>';
				$notestatus[$myrow['rateid']]	= '';
				$notessno[$myrow['rateid']]	= '';
				$r++;
			}else{
				if(!empty($rateids_arr) && in_array($myrow['rateid'], $rateids_arr)){
					$ratetype .= '<td></td>';
					$r++;
				}
				
			}
		}		
		$this->hiddenBillable[] = $hiddenBillable;			
		return $ratetype;
    }
    function displayInputField($a)
    {
	if(count($this->assignments)==1 && $a!='Y')
	{
	    //return "display:none;";
	    return "visibility:hidden;";
	}
    }
    function getRateTypesWithPayNBillSingle_UOM($asignid, $rates='', $rowid, $parid, $mode='', $req_str='', $type='', $ratesAvail,$rateids_arr='')
    {
		$req_bill_arr = explode(',',$req_str[4]);
		$req_rate_arr = explode(',',$req_str[5]);
		$uom_query ='';
		$rateHourArr = array();

		if($rates != '')
		{
			$ratesArr = explode(",", $rates);
			foreach($ratesArr as $val)
			{
				$valArr = explode("|", $val);
				if($valArr[0] == '')
				{
					$rate = 'rate1';
				}
				else
				{
					$rate = $valArr[0];
				}
				$rateHourArr[$rate] = $valArr[1];
				$billArr[$rate] = $valArr[2];	
			}
		}
		$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND t1.asgn_mode = 'hrcon' AND t1.ratetype = 'billrate' AND t1.rate!='' AND ( (t1.rate>0 AND t1.ratemasterid IN ('rate2','rate3')) OR  (t1.rate>1 AND t1.ratemasterid IN ('rate1')) OR (t1.rate>0 AND t1.ratemasterid NOT IN ('rate1','rate2','rate3'))) ORDER BY t1.sno";

		$result_ratemaster=$this->mysqlobj->query($select_ratemaster,$this->db);
		$query_count = mysql_num_rows($result_ratemaster);
		
		while($row_ratemaster=$this->mysqlobj->fetch_array($result_ratemaster))
		{
			$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = $row_ratemaster['rate'];
			$jtype = $row_ratemaster['jtype'];
		}
		
		$ratetypes = array();
		
		$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND  t2.ratetype='billrate' AND t2.asgn_mode = 'hrcon'  AND t2.rate!='' AND ( (t2.rate>0 AND t2.ratemasterid IN ('rate2','rate3')) OR  (t2.rate>1 AND t2.ratemasterid IN ('rate1')) OR (t2.rate>0 AND t2.ratemasterid NOT IN ('rate1','rate2','rate3')))) = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' AND t3.rate!='' AND ( (t3.rate>0 AND t3.ratemasterid IN ('rate2','rate3')) OR  (t3.rate>1 AND t3.ratemasterid IN ('rate1')) OR (t3.rate>0 AND t3.ratemasterid NOT IN ('rate1','rate2','rate3')))) AS billable FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' and status = 'Active' ORDER BY t1.sno";
				
		
		$ressel=$this->mysqlobj->query($select_que,$this->db);
		$rowcount = mysql_num_rows($ressel);
		
		$r = 0;
		$ratetype = '';

		while($myrow=$this->mysqlobj->fetch_array($ressel))
		{
			$hiddenBillable[$myrow['rateid']] = ($myrow['billable'] == '')?'N':$myrow['billable'];
			if(in_array($myrow['rateid'], $ratesAvail))	{
				$pay = ($rateArr[$myrow['rateid']]['payrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['payrate'];
				$bill = ($rateArr[$myrow['rateid']]['billrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['billrate'];
				$ratetype .= '<td valign="top"  class="afontstylee tsrates" align="left">';
				
				if($mode!='' && $req_str!=''){
					if($req_str[4] != '') {
						$hidbillchk = $req_bill_arr[$r];
					}
					else {
						$hidbillchk = $myrow['billable'];
					}					
					$ratetype .= '<input style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="text" value="'.$req_rate_arr[$r].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.'" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalcMuti(\'timesheetRate'.$r.'\', '.$rowid.', '.$rowcount.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).'>';
					
					$ratetype .= '<label class="container-chk" style="'.$this->displayInputField($myrow['required']).'">';
					$ratetype .= '<input style="margin-top:0px;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk($hidbillchk).' '.$this->disable($myrow['required']).'>';
					$ratetype .= '<span class="checkmark"></span>';
					$ratetype .= '</label>';
					
				}
				else {
					$ratetype .= '<input  style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="text" value="'.$rateHourArr[$myrow['rateid']].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.' '.$myrow['rateid'].' rates" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalc(\'timesheetRate'.$r.'\', this.id, '.$this->rateTypeCountSingle.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).'>';
				
					if(count($billArr) > 0)
					{
						$ratetype .= '<label class="container-chk" style="'.$this->displayInputField($myrow['required']).'">';
						$ratetype .= '<input style="margin-top:0px;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk(substr($billArr[$myrow['rateid']], 0, 1)).' >';
						$ratetype .= '<span class="checkmark"></span>';
						$ratetype .= '</label>';
					}
					else
					{
						$ratetype .= '<label class="container-chk" style="'.$this->displayInputField($myrow['required']).'">';
						$ratetype .= '<input style="margin-top:0px;vertical-align:top;'.$this->displayInputField($myrow['required']).'" type="checkbox" name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Yes" '.$this->chk($myrow['billable']).'>';
						$ratetype .= '<span class="checkmark"></span>';
						$ratetype .= '</label>';
					}
					
				}
			
				if($type != 'single')
				{
					if(SHOWPAYANDBILL == 'Y' && ($_SESSION['sess_usertype'] == 'UL' || $_SESSION['sess_usertype'] == 'BO'))
					{
						$ratetype .= "<br />P <span id='daily_rate_pay_".$r."_".$rowid."' name='daily_rate_pay_".$r."_".$rowid."'>".$pay."</span> <br />B <span id='daily_rate_bill_".$r."_".$rowid."' name='daily_rate_bill_".$r."_".$rowid."'>".$bill."<span>";
					}
				}				
				$ratetype .= '</td>';
				$r++;
			}else{
				if(!empty($rateids_arr) && in_array($myrow['rateid'], $rateids_arr)){
					$ratetype .= '<td></td>';
					$r++;
				}
				
			}
		}		
		$this->hiddenBillable[] = $hiddenBillable;			
		return $ratetype;
    }	
    function getRateTypesWithPayNBill_multi($asignid, $rates='', $rowid, $mode='', $req_str='')
    {
		$req_bill_arr = explode(',',$req_str[4]);
		$req_rate_arr = explode(',',$req_str[5]);
	
		$rateHourArr = array();
	
		if($rates != '')
		{
			$ratesArr = explode(",", $rates);
			foreach($ratesArr as $val)
			{
			$valArr = explode("|", $val);
			if($valArr[0] == '')
			{
				$rate = 'rate1';
			}
			else
			{
				$rate = $valArr[0];
			}
			$rateHourArr[$rate] = $valArr[1];
			$billArr[$rate] = $valArr[2];	
			}
		}
		$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND period =  'HOUR' AND t1.asgn_mode = 'hrcon' ORDER BY t1.sno";

		$result_ratemaster=$this->mysqlobj->query($select_ratemaster,$this->db);
		
		while($row_ratemaster=$this->mysqlobj->fetch_array($result_ratemaster))
		{
			$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = $row_ratemaster['rate'];
			$jtype = $row_ratemaster['jtype'];
		}
	
		$ratetypes = array();
		$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND t2.asgn_mode = 'hrcon') = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon') AS billable FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' and status = 'Active' ORDER BY t1.sno";

		$ressel=$this->mysqlobj->query($select_que,$this->db);
		$rowcount = mysql_num_rows($ressel);
	
		($jtype == 'OP')?$asgn = 'Y' : $asgn = 'N';
		$r = 0;
		$ratetype = '';
		
		while($myrow=$this->mysqlobj->fetch_array($ressel))
		{
			if($rates == '')
			{
				$bill1 = $myrow['billable'];		
			}
			else
			{
			if($billArr[$myrow['rateid']] != '')
			{
				$bill1 = $billArr[$myrow['rateid']];
				$bill1 = substr($bill1, 0, 1);
			}
			else
			{
				$bill1 = 'N';
			}
			
			}
			if($asignid == 0)
			{
				$asgn = 'N';
			}

			$pay = ($rateArr[$myrow['rateid']]['payrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['payrate'];
			$bill = ($rateArr[$myrow['rateid']]['billrate'] == '')?'0.00':$rateArr[$myrow['rateid']]['billrate'];
			$ratetype .= '<td valign="top" class="afontstylee">';
			
			if($mode!='' && $req_str!=''){		
				$hidbillchk = ($req_bill_arr[$r]=='Y') ? 'checked':'';
				// on keyup added timesheetcalcmuti instead of timesheetcal function
				$ratetype .= '<input style="height:18px; height:16px \0/; padding-top:0px;padding-top:0px \0/;vertical-align:top;" type="text" value="'.$req_rate_arr[$r].'" size="3" max_length="5" maxlength="6" class="timesheetRate'.$r.'" name="daily_rate_'.$rowid.'['.$rowid.']['.$myrow['rateid'].']" id="daily_rate_'.$r.'_'.$rowid.'" onkeyup="TimesheetCalcMuti(\'timesheetRate'.$r.'\', '.$rowid.', '.$rowcount.', \'daily_'.$myrow['rateid'].'_'.$r.'_'.$rowid.'\')" '.$this->disable($myrow['required']).'>';
				
				$ratetype .= '<input style="margin-top:0px;vertical-align:top" type="checkbox"  '.$hidbillchk.'  name="daily_rate_billable_'.$rowid.'['.$myrow['rateid'].']" id="daily_rate_billable_'.$r.'_'.$rowid.'" value="Y" '.$hidbillchk.' '.$this->disable($myrow['required']).'>';
			}
			
			$ratetype .= "<br />P <span id='daily_rate_pay_".$r."_".$rowid."' name='daily_rate_pay_".$r."_".$rowid."'>".$pay."</span> <br />B <span id='daily_rate_bill_".$r."_".$rowid."' name='daily_rate_bill_".$r."_".$rowid."'>".$bill."<span>";
			$ratetype .= '</td>';
			$r++;
		}
		return $ratetype;
    }
    
    function getRateTypesWithPayNBillAjax($asignid, $rowid,$mod_mul='',$ts_type='')
    {
		$uom_query ='';
		$ratesArr = array();
		if(strpos($asignid , 'earn'))
		{
			//get all the rates
			$allRates = $this->getRateTypes();
			$rtype = count($allRates);	    
			for($i = 0; $i < $rtype; $i++)
			{
				if($i < 3)
				{
					$required = 'Y';
					$billable = 'N';
				}
				else
				{
					$required = 'N';
					$billable = 'N';
				}
				$ratesArr[] = "daily_rate_".$i."_".$rowid.",".$required.",daily_rate_billable_".$i."_".$rowid.",".$billable.", daily_rate_pay_".$i."_".$rowid.",".$rateArr[$myrow['rateid']]['payrate'].", daily_rate_bill_".$i."_".$rowid.",".$rateArr[$myrow['rateid']]['billrate'].",".$allRates[$i]['rateid'];
			}
		}
		else
		{
			
				if($ts_type == 'UOM' || $ts_type == 'Custom'){
					$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."'  AND t1.asgn_mode = 'hrcon' AND ratetype =  'billrate' AND t1.rate!='' AND ( (t1.rate>0 AND t1.ratemasterid IN ('rate2','rate3')) OR  (t1.rate>1 AND t1.ratemasterid IN ('rate1')) OR (t1.rate>0 AND t1.ratemasterid NOT IN ('rate1','rate2','rate3'))) ORDER BY t1.sno";
					
				}
				else if($mod_mul !='' && $mod_mul == 'multi_asgn'){
					$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype, manage.name FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno  INNER JOIN manage ON manage.sno=t2.jotype where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND  t1.asgn_mode = 'hrcon' ORDER BY t1.sno";
				}
				else{
					$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND  t1.asgn_mode = 'hrcon' ORDER BY t1.sno";
				}
			
			$result_ratemaster=$this->mysqlobj->query($select_ratemaster,$this->db);
			
			while($row_ratemaster=$this->mysqlobj->fetch_array($result_ratemaster))
			{
				if($row_ratemaster['name'] == 'Internal Direct' && $row_ratemaster['ratemasterid'] == 'rate1' && $mod_mul == 'multi_asgn'){
					$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = '0.00';
				}else{
					$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = $row_ratemaster['rate'];
				}
				$jtype = $row_ratemaster['jtype'];
			}

	    $ratetypes = array();
		if(($ts_type == 'UOM' || $ts_type == 'Custom') || ($mod_mul !='' && $mod_mul == 'multi_asgn')){
			$mult_asgn = '';
			if($mod_mul == 'multi_asgn'){
				//$mult_asgn = " AND period='HOUR' ";
				$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND  t2.ratetype='billrate' AND t2.asgn_mode = 'hrcon') = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' ".$mult_asgn.") AS billable,(SELECT t4.period FROM multiplerates_assignment t4 WHERE t4.asgnid = '".$asignid."' AND t4.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' ".$mult_asgn.") AS period FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' AND status='ACTIVE'  ORDER BY t1.sno";
			}
			else{
			  	$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND  t2.ratetype='billrate' AND t2.asgn_mode = 'hrcon'  AND t2.rate!='' AND ( (t2.rate>0 AND t2.ratemasterid IN ('rate2','rate3')) OR  (t2.rate>1 AND t2.ratemasterid IN ('rate1')) OR (t2.rate>0 AND t2.ratemasterid NOT IN ('rate1','rate2','rate3')))) = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon'  AND t3.rate!=''  AND ( (t3.rate>0 AND t3.ratemasterid IN ('rate2','rate3')) OR  (t3.rate>1 AND t3.ratemasterid IN ('rate1')) OR (t3.rate>0 AND t3.ratemasterid NOT IN ('rate1','rate2','rate3')))) AS billable,(SELECT t4.period FROM multiplerates_assignment t4 WHERE t4.asgnid = '".$asignid."' AND t4.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon'  AND t4.rate!=''  AND ( (t4.rate>0 AND t4.ratemasterid IN ('rate2','rate3')) OR  (t4.rate>1 AND t4.ratemasterid IN ('rate1')) OR (t4.rate>0 AND t4.ratemasterid NOT IN ('rate1','rate2','rate3')))) AS period FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' AND status='ACTIVE'  ORDER BY t1.sno";
			}
		}
		else{
			$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND t2.asgn_mode = 'hrcon') = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon'  ) AS billable,(SELECT t4.period FROM multiplerates_assignment t4 WHERE t4.asgnid = '".$asignid."' AND t4.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' ) AS period FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' AND status='ACTIVE'  ORDER BY t1.sno";
		}
	    $ressel=$this->mysqlobj->query($select_que,$this->db);
	    $rowcount = mysql_num_rows($ressel);
	    
	    $r = 0;
	    $ratetype = '';
    
	    while($myrow=$this->mysqlobj->fetch_array($ressel))
	    {
		if($asignid == '')
		{
		    $required = 'N';
		    $billable = 'N';
		}
		else
		{
			if(($ts_type != 'UOM' || $ts_type != 'Custom') && $mod_mul != 'multi_asgn'){
			
				if($jtype != 'OP')
				{
					if($myrow['rateid'] == 'rate1' || $myrow['rateid'] == 'rate2' || $myrow['rateid'] == 'rate3')
					{
						if($ts_type == 'UOM' || $ts_type == 'Custom'){
							$required = 'N';
							$billable = 'N';
						}else{
							$required = 'Y';
							$billable = 'N';
						}
						
					}
					else
					{
						$required = 'N';
						$billable = 'N';
					}
				}
				else
				{
					$required = $myrow['required'];
					$billable = $myrow['billable'];
				}
			}
			else
			{
				$required = $myrow['required'];
				$billable = $myrow['billable'];
			}
		}
		/*if($mod_mul == 'multi_asgn' && $myrow['period']!='HOUR'){
		
			$rateArr[$myrow['rateid']]['payrate'] = '0.00';
			$rateArr[$myrow['rateid']]['billrate'] = '0.00';
		}*/
		$ratesArr[] = "daily_rate_".$r."_".$rowid.",".$required.",daily_rate_billable_".$r."_".$rowid.",".$billable.", daily_rate_pay_".$r."_".$rowid.",".$rateArr[$myrow['rateid']]['payrate'].", daily_rate_bill_".$r."_".$rowid.",".$rateArr[$myrow['rateid']]['billrate'].",".$myrow['rateid'];
		$r++;
	    }
	}
	$ratesArr[] = $asignid;
	return implode("|", $ratesArr);
    }

    // this function is used to get the all assignment rates
    function getAllRateTypesWithPayNBillPusernames($rowid,$mod_mul='',$ts_type='',$listOfAssignmentids='')
    {
		$uom_query ='';
		$ratesArr = array();
		$ratesRowArr = array();
		$rateidArr = array();
		$asignids ='';
		if(strpos($asignid , 'earn'))
		{
			//get all the rates
			$allRates = $this->getRateTypes();
			$rtype = count($allRates);	    
			for($i = 0; $i < $rtype; $i++)
			{
				if($i < 3)
				{
					$required = 'Y';
					$billable = 'N';
				}
				else
				{
					$required = 'N';
					$billable = 'N';
				}
				$ratesArr[] = "daily_rate_".$i."_".$rowid.",".$required.",daily_rate_billable_".$i."_".$rowid.",".$billable.", daily_rate_pay_".$i."_".$rowid.",".$rateArr[$myrow['rateid']]['payrate'].", daily_rate_bill_".$i."_".$rowid.",".$rateArr[$myrow['rateid']]['billrate'].",".$allRates[$i]['rateid'];
			}
		}
		else
		{
			$pusernames = "'" . implode ( "', '", $listOfAssignmentids ) . "'";
			$select_hrcon = "SELECT GROUP_CONCAT(sno) AS assignsnos FROM hrcon_jobs WHERE pusername IN(".$pusernames.") ";
			$result_select_hrcon =$this->mysqlobj->query($select_hrcon,$this->db);
			$row_select_hrcon =$this->mysqlobj->fetch_array($result_select_hrcon);

			if (!empty($row_select_hrcon['assignsnos'])) {
				$asignids = $row_select_hrcon['assignsnos'];	
			}
			if (!empty($asignids)) {
				$asignid = $asignids;
			}
			$selAssignRatesArry = array();
			$getAssignsRates = "SELECT t1.ratemasterid FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno WHERE t1.ratemasterid != 'rate4' AND t1.asgnid IN (".$asignid.")  AND t1.asgn_mode = 'hrcon' AND t1.ratetype = 'billrate' AND t1.rate!='' AND ( (t1.rate>0 AND t1.ratemasterid IN ('rate2','rate3')) OR  (t1.rate>1 AND t1.ratemasterid IN ('rate1')) OR (t1.rate>0 AND t1.ratemasterid NOT IN ('rate1','rate2','rate3'))) ORDER BY t1.sno";
			$result_getAssignsRates=$this->mysqlobj->query($getAssignsRates,$this->db);
			while($row_assignsRates=$this->mysqlobj->fetch_array($result_getAssignsRates))
			{
				if (!in_array($row_assignsRates['ratemasterid'], $selAssignRatesArry)) {
					array_push($selAssignRatesArry, $row_assignsRates['ratemasterid']);
				}
			}
			$ratetypes = array();
			$rateMasterArry = array();
			$assignSnos = explode(",",$asignids);
			for ($i=0; $i < count($assignSnos); $i++) { 
				$assignSno = $assignSnos[$i];
				if ($assignSno !="") {
					$asignid = $assignSno;
					$uom_query ='';
					$rateArr =array();
					if(strpos($asignid , 'earn'))
					{
						//get all the rates
						$allRates = $this->getRateTypes();
						$rtype = count($allRates);	    
						for($i = 0; $i < $rtype; $i++)
						{
							if($i < 3)
							{
								$required = 'Y';
								$billable = 'N';
							}
							else
							{
								$required = 'N';
								$billable = 'N';
							}
							$ratesArr[] = "daily_rate_".$i."_".$rowid.",".$required.",daily_rate_billable_".$i."_".$rowid.",".$billable.", daily_rate_pay_".$i."_".$rowid.",".$rateArr[$myrow['rateid']]['payrate'].", daily_rate_bill_".$i."_".$rowid.",".$rateArr[$myrow['rateid']]['billrate'].",".$allRates[$i]['rateid'];
						}
					}
					else
					{
						if($ts_type == 'UOM' || $ts_type == 'Custom')
						{
							$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."'  AND t1.asgn_mode = 'hrcon' AND ratetype =  'billrate' AND t1.rate!='' AND ( (t1.rate>0 AND t1.ratemasterid IN ('rate2','rate3')) OR  (t1.rate>1 AND t1.ratemasterid IN ('rate1')) OR (t1.rate>0 AND t1.ratemasterid NOT IN ('rate1','rate2','rate3'))) ORDER BY t1.sno";
							
						}
						else if($mod_mul !='' && $mod_mul == 'multi_asgn')
						{
							$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype, manage.name FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno  INNER JOIN manage ON manage.sno=t2.jotype where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND  t1.asgn_mode = 'hrcon' ORDER BY t1.sno";
						}
						else
						{
							$select_ratemaster = "SELECT t1.ratemasterid, t1.ratetype, t1.rate, t2.jtype FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t2 ON t1.asgnid = t2.sno where ratemasterid != 'rate4' AND asgnid = '".$asignid."' AND  t1.asgn_mode = 'hrcon' ORDER BY t1.sno";
						}
						$result_ratemaster=$this->mysqlobj->query($select_ratemaster,$this->db);
						while($row_ratemaster=$this->mysqlobj->fetch_array($result_ratemaster))
						{
							if($row_ratemaster['name'] == 'Internal Direct' && $row_ratemaster['ratemasterid'] == 'rate1' && $mod_mul == 'multi_asgn'){
								$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = '0.00';
							}else{
								$rateArr[$row_ratemaster['ratemasterid']][$row_ratemaster['ratetype']] = $row_ratemaster['rate'];
							}
							$jtype = $row_ratemaster['jtype'];
							$rateMasterArry[] = $row_ratemaster['ratemasterid'];
						}

						$rateids = "'" . implode ( "', '", $rateMasterArry ) . "'";
						if(($ts_type == 'UOM' || $ts_type == 'Custom') || ($mod_mul !='' && $mod_mul == 'multi_asgn'))
						{
							$mult_asgn = '';
							if($mod_mul == 'multi_asgn'){
								//$mult_asgn = " AND period='HOUR' ";
								$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND  t2.ratetype='billrate' AND t2.asgn_mode = 'hrcon') = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' ".$mult_asgn.") AS billable,(SELECT t4.period FROM multiplerates_assignment t4 WHERE t4.asgnid = '".$asignid."' AND t4.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' ".$mult_asgn.") AS period FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' AND status='ACTIVE'  ORDER BY t1.sno";
							}
							else
							{
							  	//AND t1.rateid IN(".$rateids.")
								$select_que	= " SELECT   
													t1.rateid as rateid, 
													IF(COUNT(1) = 0, 'N', 'Y') as required, 
													t2.billable as billable, 	
													t2.period as period
												FROM     multiplerates_master t1
														 LEFT JOIN multiplerates_assignment t2 ON t2.ratemasterid = t1.rateid AND t2.ratetype = 'billrate' AND t2.asgn_mode = 'hrcon'
												WHERE    t1.rateid != 'rate4' AND t1.status = 'ACTIVE' AND t2.rate != '' AND ((t2.rate > 0 AND t2.ratemasterid IN ('rate2', 'rate3'
														 )) OR (t2.rate > 1 AND t2.ratemasterid = 'rate1') OR (t2.rate > 0 AND t2.ratemasterid NOT IN ('rate1', 'rate2', 'rate3'))
														 ) AND t2.asgnid = '".$asignid."'
												GROUP BY t1.rateid
												ORDER BY t1.sno"; 
							}
						}
						else
						{
							$select_que="SELECT t1.rateid, (IF((SELECT COUNT(1) FROM multiplerates_assignment t2 WHERE t2.asgnid = '".$asignid."' AND t2.ratemasterid = t1.rateid AND t2.asgn_mode = 'hrcon') = 0,'N','Y')) AS required, (SELECT t3.billable FROM multiplerates_assignment t3 WHERE t3.asgnid = '".$asignid."' AND t3.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon'  ) AS billable,(SELECT t4.period FROM multiplerates_assignment t4 WHERE t4.asgnid = '".$asignid."' AND t4.ratemasterid = t1.rateid  AND ratetype='billrate' AND asgn_mode = 'hrcon' ) AS period FROM multiplerates_master t1 WHERE t1.rateid != 'rate4' AND status='ACTIVE'  ORDER BY t1.sno";
						}
					    $ressel=$this->mysqlobj->query($select_que,$this->db);
					    $rowcount = mysql_num_rows($ressel);
					    
					    $r = 0;
					    $ratetype = '';
					    $assignRatesArry = array();
					    while($myrow=$this->mysqlobj->fetch_array($ressel))
					    {
							if($asignid == '')
							{
							    $required = 'N';
							    $billable = 'N';
							}
							else
							{
								if(($ts_type != 'UOM' || $ts_type != 'Custom') && $mod_mul != 'multi_asgn'){
								
									if($jtype != 'OP')
									{
										if($myrow['rateid'] == 'rate1' || $myrow['rateid'] == 'rate2' || $myrow['rateid'] == 'rate3')
										{
											if($ts_type == 'UOM' || $ts_type == 'Custom'){
												$required = 'N';
												$billable = 'N';
											}else{
												$required = 'Y';
												$billable = 'N';
											}
											
										}
										else
										{
											$required = 'N';
											$billable = 'N';
										}
									}
									else
									{
										$required = $myrow['required'];
										$billable = $myrow['billable'];
									}
								}
								else
								{
									$required = $myrow['required'];
									$billable = $myrow['billable'];
								}
							}
							$myrowPeriod = "Hours";
							if ($myrow['period'] !=NULL) {
								$myrowPeriod = $this->getTooltip($myrow['period']);
							}								
							$daily_rateval.= '"daily_rate_'.$r.'_'.$rowid.','.$required.',daily_rate_billable_'.$r.'_'.$rowid.','.$billable.', daily_rate_pay_'.$r.'_'.$rowid.','.$rateArr[$myrow['rateid']]['payrate'].', daily_rate_bill_'.$r.'_'.$rowid.','.$rateArr[$myrow['rateid']]['billrate'].','.$myrow['rateid'].','.$myrowPeriod.'",';

/* 							$daily_rateval1= "'daily_rate_".$r."_".$rowid.",".$required.",daily_rate_billable_".$r."_".$rowid.",".$billable.", daily_rate_pay_".$r."_".$rowid.",".$rateArr[$myrow['rateid']]['payrate'].", daily_rate_bill_".$r."_".$rowid.",".$rateArr[$myrow['rateid']]['billrate'].",".$myrow['rateid'].","; */

							if (!array_key_exists($asignid, $ratesArr)) {
								$jsnokeyVal = $asignid;
								$ratesArr[$asignid] = array();
							}
							if (!in_array($myrow['rateid'], $assignRatesArry)) {
					    		array_push($assignRatesArry, $myrow['rateid']);
					    	}
						$r++;
					    }
					    foreach ($selAssignRatesArry as $assignRates) {
					    	if (!in_array($assignRates, $assignRatesArry)) {
					    		
					    		$daily_rateval.= '"daily_rate_'.$r.'_0,N,daily_rate_billable_'.$r.'_0,, daily_rate_pay_'.$r.'_0,, daily_rate_bill_'.$r.'_0,,'.$assignRates.'",';
					    		$r++;
					    	}					    	
					    }
					    
					    	$daily_rateval = substr($daily_rateval, 0, -1);
					   	$jsnoRatesVal.= '{"'.$jsnokeyVal.'":['.$daily_rateval.']},';
					   	$daily_rateval='';
					   	$jsnokeyVal ='';
					}
				}
			}
		}
		$jsnoRatesVal = substr($jsnoRatesVal, 0, -1);
	
	        return $totalRateVal = '{"Rates":['.$jsnoRatesVal.']}';
	
    }

	//Added ts_type for Timesheet difference
	function buildCheckBox_TotalHours($name, $asgnids_all,$ts_type='')
	{
		$asgnArr = $this->getRateTypesForAllAsgnnames($asgnids_all,'',$ts_type);
		foreach($asgnArr as $key => $val)
		{
			$totalHrsValuesArray = $this->eachrowidTotalValArr[$key];
			if (isset($totalHrsValuesArray['Hours'])) {

				$HOUR = number_format(array_sum($totalHrsValuesArray['Hours']),2,'.','');
				//$UOM_DAY = $UOM_UNIT = $UOM_MILE = '0.00';
			}
			if (isset($totalHrsValuesArray['Day'])) {

				$UOM_DAY = number_format(array_sum($totalHrsValuesArray['Day']),2,'.','');
				//$HOUR = $UOM_UNIT = $UOM_MILE = '0.00';
			}
			if (isset($totalHrsValuesArray['Miles'])) {

				$UOM_MILE = number_format(array_sum($totalHrsValuesArray['Miles']),2,'.','');
				//$HOUR = $UOM_DAY = $UOM_UNIT = '0.00';
			}
			if (isset($totalHrsValuesArray['Units'])) {

				$UOM_UNIT = number_format(array_sum($totalHrsValuesArray['Units']),2,'.','');
				//$HOUR = $UOM_DAY = $UOM_MILE = '0.00';
			}
			
			$ts_inpdiv = '';
			if($ts_type == 'UOM' || $ts_type == 'Custom') {
				$ts_inpdiv ="<input type='hidden' name='{$name}{$key}_day_input' value='".$UOM_DAY."' size='5'><div id='{$name}{$key}_day_div' style='display:;' class='dayDiv tsDiv dayDivVal'>".$UOM_DAY."</div><input type='hidden' name='{$name}{$key}_mile_input' value='".$UOM_MILE."' size='5'><div id='{$name}{$key}_mile_div' style='display:;' class='mileDiv tsDiv mileDivVal'>".$UOM_MILE."</div><input type='hidden' name='{$name}{$key}_unit_input' value='".$UOM_UNIT."' size='5'><div id='{$name}{$key}_unit_div' style='display:;' class='unitDiv tsDiv unitDivVal'>".$UOM_UNIT."</div>";
			}
			echo "<td class='totbg' align='left'><input type='hidden' name='{$name}{$key}_input' value='".$HOUR."' size='5'><div id='{$name}{$key}_div' style='display:;' class='hrsDiv tsDiv hrsDivVal'>".$HOUR."</div>".$ts_inpdiv."</td>";

			$HOUR = $UOM_DAY = $UOM_UNIT = $UOM_MILE = '0.00';
		}
	}

    //Function for getting the Total Hours/Miles/Units/Days
    function build_TotalHours($asgnids_all,$ts_type='')
    {
		$asgnArr = $this->getRateTypesForAllAsgnnames($asgnids_all,'',$ts_type);
		foreach($asgnArr as $key => $val)
		{
			echo "<td class='totbg' align='left'></td>";
		}
    }
    function getEmployees($module, $username, $assign_start_date, $assign_end_date)
    {
		$fun = 'get'.$module.'EmployeeNames';
		$names = $this->{$fun}($username, $assign_start_date, $assign_end_date);
		return $names;
    }
    
    function getAssignId($assngid)
    {
		$sql = "select sno from hrcon_jobs where pusername = '".$assngid."'";
		$result = $this->mysqlobj->query($sql,$this->db);
		while($result=$this->mysqlobj->fetch_array($result))
		{
			$id = $result['sno'];
		}
		
		return $id;
    }
    //Added ts_type for Timesheet difference
    function getRangeRow($employee, $assign_id = '', $rtype = '', $task='', $assignStartEndDate, $assignStartDate, $assignEndDate, $classid, $rowid, $range='no', $timesheet_hours_sno = '', $edit_string = '', $editRowid='',$module='', $rowtotal='0.00', $cval = '',$ts_type='',$rateids_arr='')
    {
		$this->mystr = array();
		$this->mystr[] = $timesheet_hours_sno;

		$dayWeekClass = ($range=="yes") ? "dayWeekTab2" : "dayWeekTab1";
		$rangRow = "<tr id='row_".$rowid."' class='tr_clone ".$dayWeekClass."'>";
		
		////////////////// Dates dropdown ///////////////////////////
		$rangRow .= "<td valign='top' width='2%' class='DeletePad'>
		<input type='hidden' id='edit_string' name='edit_string[".$rowid."]' value='".$edit_string."'>
		<input type='hidden' id='edit_snos_new' name='edit_snos_new[".$rowid."]' value='".$timesheet_hours_sno."'>
		<input type='checkbox' name='daily_check[".$rowid."][]' id='check_".$rowid."' value='".$timesheet_hours_sno."' class='chremove' style='margin-top:0px; display:none;' >";
		
		$rangRow .="<span name='daily_del[".$rowid."][]' id='dailydel_".$rowid."' onclick='javascript:delCloneRow(this.id)'><i class='fa fa-trash fa-2x' alt='Delete' Title='Delete'></i></span></td>";
		
		//$rangRow .= "<span name='daily_check[".$rowid."][]' id='check_".$rowid."' ><i class='fa fa-plus-square fa-lg'></i></span><span name='daily_check1[".$rowid."][]' id='check1_".$rowid."' ><i class='fa fa-trash fa-lg'></i></span></td>";
		
		$rangRow .= "<td valign='top' align='left' width='10%'>"; 
		
		$rangRow .= $this->buildDropDownCheck('daily_dates', $rowid, $assignStartEndDate, $assignStartDate, $script='', $key='', $val='', $range, $employee,false,'' ,$module ,$cval);
		$rangRow .= "<font title='click here to add task details' onclick='javascript:AddTaskDetails(this.id)' id='addtaskdetails_".$rowid."' class='addtaskBtn' style='padding-top: 0px; white-space:nowrap;'><i class='fa fa-tasks fa-lg'></i>Add Task Details

 </font>";
		$rangRow .= "</td>";
		
		////////////////// Assignments dropdown ///////////////////////////
		$asgnDropDown = $this->getAssignments($employee, $assign_id, $assignStartDate, $assignEndDate, $rowid,$module,'','',$cval,$ts_type);
		if(count($this->assignments) > 1)
		{
			$multicss = "background='/PSOS/images/arrow-multiple-12-red.png' style='background-repeat:no-repeat;background-position:left 12px; padding-left: 17px;word-break:break-all;overflow-wrap: break-word;'";
		}else{
			$multicss = "style='word-break:break-all;overflow-wrap: break-word;'";
		}
		$rangRow .= "<td valign='top' class='nowrap ' width='32%' ".$multicss." >";
		$rangRow .= '<span id="span_'.$rowid.'">';
		$rangRow .= $asgnDropDown;
		$rangRow .= '</span>';
		$rangRow .= "<br />";
		$rangRow .= "<label id='textlabel_".$rowid."' title='click here to add task details' class='afontstylee textwrampnew' onclick='javascript:AddTaskDetails(this.id)'  style='display:inline;padding-top: 0px;float:left'>".$task."</label>";
		$rangRow .= "<input style='display: none;' class='addtaskdetails' type='text' class=afontstylee name='daily_task[0][".$rowid."]'  value='".html_tls_specialchars($task,ENT_QUOTES)."' id='np_".$rowid."' tabindex='10'>";
		$rangRow .= "</td>";
		if(MANAGE_CLASSES == 'Y')
		{
			////////////////// Classes dropdown ///////////////////////////
			$rangRow .= "<td valign='top' width='8%'>";
			$rangRow .= $this->buildDropDownClasses('daily_classes', $rowid, $this->getClasses(), $classid, '','sno', 'classname');
			$rangRow .= "</td>";
		}
		if(strpos($assign_id, 'earn'))
		{
			$assignment_id = ($assign_id=='')?$this->assignmentIds[0]:$assign_id;
		}
		else
		{
			$assignment_id = ($assign_id=='')?$this->assignmentIds[0]:$this->getAssignId($assign_id);
		}
		if($ts_type == 'UOM' || $ts_type == 'Custom'){
			$rangRow .= "<div id='raterow_".$rowid."'>".$this->getRateTypesWithPayNBillSingle_UOM($assignment_id, $rtype, $rowid, $par_id, '', '', 'single', $this->getRateTypesForAllAsgnnames($this->listOfAssignments,'','UOM'),$rateids_arr)."<div>";
		}
		else{
			$rangRow .= "<div id='raterow_".$rowid."'>".$this->getRateTypesWithPayNBillSingle($assignment_id, $rtype, $rowid, $par_id, '', '', 'single', $this->getRateTypesForAllAsgnnames($this->assignments),'')."<div>";
		}
		
		///////////////////////// Total hours /////////////////////////

		$tuom = '';
		if($ts_type == 'UOM' || $ts_type == 'Custom') {//For UOM Timesheet input hidden fields for totals
			$tuom = "<input type='hidden' name='totaluomdays_".$rowid."' id='totaluomdays_".$rowid."' value='".$rowtotal."' ><input type='hidden' name='totaluommiles_".$rowid."' id='totaluommiles_".$rowid."' value='".$rowtotal."' ><input type='hidden' name='totaluomunits_".$rowid."' id='totaluomunits_".$rowid."' value='".$rowtotal."' >
					<input type='text' name='daystotalDiv_".$rowid."' id='daystotalDiv_".$rowid."' value='".$rowtotal."' style='display:none;'><input type='text' name='milestotalDiv_".$rowid."' id='milestotalDiv_".$rowid."' value='".$rowtotal."' style='display:none;'><input type='text' name='unitstotalDiv_".$rowid."' id='unitstotalDiv_".$rowid."' value='".$rowtotal."' style='display:none;'>";
		}
		$rangRow .= "<td valign='top' class='afontstylee' width='3%'><input type='hidden' name='daytotalhrs_".$rowid."' id='daytotalhrs_".$rowid."' value='".$rowtotal."' >".$tuom."<input type='hidden' name='editrow[]' id='editrow_".$rowid."' value='".$editRowid."' ><input type='text' name='daytotalhrsDiv_".$rowid."' id='daytotalhrsDiv_".$rowid."' value='".$rowtotal."' style='display:none;'></td>";
		
		$rangRow .= '</tr>';
		
		//Adding script to append new select for rendered html selects.
		$rangRow .= "<script>var customSelectElement = $('#MainTable #row_".$rowid."  select');bindSelect2(customSelectElement);</script>";
		
		return $rangRow;
    }
    
    function buildHeaders($headers, $types)
    {
		$str='';
		foreach($types as $typekey => $typeval){
			//array_push($headers,$typeval['name']."<br/> Hours Billable");
			array_push($headers,"<table border=0><tr><td colspan='2' align='center' ><font class=afontstylee>".$typeval['name']."</font></td></tr><tr style='line-height:10px;'><td><font class=afontstylee>Hours</font></td><td align='left'>
			<a href='#' style='text-decoration:none;' class=afontstylee>
			<span class='caption afontstylee'>Billable</span>
			<img src='/BSOS/images/dollar.png' width='10px' />
			</a>
			</td></tr></table>");
		}		
		foreach($headers as $key => $val){
			$str .= "<td><font class=afontstyle>".$val."</font></td>";
		}
		return $str;
    }
    function buildHeadersDis($headers, $types)
    {
		$str='';
		foreach($types as $typekey => $typeval){
			//array_push($headers,$typeval['name']."<br/> Hours Billable");
			array_push($headers,"<th valign='top' class='nowrap' valign='left'><font class=afontstylee>".$typeval['name']."</font></th>");
		}
		
		foreach($headers as $key => $val){
			$str .= $val;
		}
		return $str;
    }
    function buildHeadersSym($headersSym, $types)
    {
	    $str='';
		foreach($types as $typekey => $typeval){
			array_push($headersSym,"<td valign='top' class='bold'><table><tr><td><font class=afontstylee><b> Hours </font></td><td class='t-r'><font class=afontstylee><label title='Billable'>$</label></b></span></font></td></tr></table></td>");
		}
		
		foreach($headersSym as $key => $val){
			$str .= $val;
		}
		return $str;
    }
    //Added ts_type for Timesheet difference
    function buildDynamicHeaders($defHeaders, $asgnids_all,$ts_type='')
    {
		//$asgnArr = $this->getRateTypesForAllAsgn($asgnids_all);
		$asgnArr = $this->getRateTypesForAllAsgnnames($asgnids_all,'',$ts_type);
		
		$str = '';
		$headerCount = count($defHeaders);
		$ratetype = $this->getRateTypes();

		foreach($ratetype as $val)
		{
			if(in_array($val['rateid'], $asgnArr))
			{
				array_push($defHeaders, $val['name']);
			}
		}
		$str .= '<tr class=hthbgcolor><th >&nbsp;</th>';
		foreach($defHeaders as $val)
		{
			$str .= '<th valign="top" class="nowrap" align="left"><font class=afontstylee>'.$val.'</font></th>';
			
		}	
		$str .= "<th>&nbsp;</th></tr></thead>";
		$str .= '<tr class=hthbgcolor><td style="background-color: white">&nbsp;</td>';
		$header = 0;
		foreach($defHeaders as $val)
		{
		   if($header >= $headerCount)
			{
			if($ts_type!=''){//Added for UOM timesheet for UOM label in timesheets
				$str .= '<td valign="top" style="background-color: white"><font class=afontstylee><b>  </font><font class=afontstylee> <label  title="Billable"><span style="margin-left:18px; -moz-margin-start:0px;"></span></label></b></font></td>';
			}else{
				$str .= '<td valign="top" align="left"><font class=afontstylee><b> Hours </font><font class=afontstylee> <label  title="Billable"><span style="margin-left:18px; -moz-margin-start:0px;">$</span></label></b></font></td>';
			}
			}
			else
			{
				$str .= '<td valign="top" style="background-color: white">&nbsp;</td>';
			}
			$header++;			
		}
		$str .= "<td style='background-color: white'>&nbsp;</td></tr>";
		return $str;
    }
    
    function buildDynamicHeaders_multi($defHeaders)
    {
		$str = '';
		$headerCount = count($defHeaders);
		$ratetype = $this->getRateTypes();
		foreach($ratetype as $val)
		{
			array_push($defHeaders, $val['name']);
		}
		$str .= '<tr class=hthbgcolor><th >&nbsp;</th>';
		foreach($defHeaders as $val)
		{
			$str .= '<th valign="top" class="nowrap" align="left"><font class=afontstylee>'.$val.'</font></th>';
			
		}	
		$str .= "<th valign='top' align='left'><font class=afontstylee>Total</font></th></tr>";
		$str .= '<tr class=hthbgcolor><td style="background-color: white">&nbsp;</td>';
		$header = 0;
		foreach($defHeaders as $val)
		{
		   if($header >= $headerCount)
			{
				//Removed Hours and $ for UOM multiple timesheet
				$str .= '<td valign="top" style="background-color: white"><font class=afontstylee><b>  </font><font class=afontstylee> <label  title="Billable"><span style="margin-left:18px; -moz-margin-start:10px;"></span></label></b></font></td>';
			
			}
			else
			{
				$str .= '<td valign="top" style="background-color: white">&nbsp;</td>';
			}
			$header++;
		}
		$str .= "<td style='background-color: white'>&nbsp;</td></tr>";
		return $str;
    }
    
    function buildMainHeaders($mainHeaders,$mode)
    {
		$arrMode = array('approved' => 'Approved','exported' => 'Approved','rejected' => 'Rejected','deleted' => 'Deleted','Rejected' => 'Rejected','approvedexp'=>'Approved');
		$str = '<tr class=hthbgcolorr>';
		if($mode == 'create')
		{
			$str .= '<th valign="top" class="nowrap">&nbsp;</th>';
		}
		foreach($mainHeaders as $val)
		{
			$str .= '<th valign="top" class="nowrap"><font class=afontstylee>'.$val.'</font></th>';
		}
		if(trim($mode) != 'pending' && trim($mode) !='errejected' && trim($mode) !='erer' && trim($mode) !='create' && trim($mode) !='Saved' && trim($mode) != '' && trim($mode)!= 'backup') {
			$str .= '<th valign="top" class="nowrap"><font class=afontstylee>'.$arrMode[$mode].' By</font></th>';
			$str .= '<th valign="top" class="nowrap"><font class=afontstylee>'.$arrMode[$mode].' Time</font></th>';
		}
		if($mode == 'exported') 
		{			
			$str .= '<th valign="top" class="nowrap"><font class=afontstylee>Exported By</font></th>';
			$str .= '<th valign="top" class="nowrap"><font class=afontstylee>Exported Date</font></th>';
		}
		$str .= '</tr>';
		
		return $str;
    }
	
    //Added ts_type for Timesheet difference
    function buildSubHeaders($mainHeaders, $headerCount,$mode,$ts_type='')
    {
		$arrMode = array('approved' => 'Approved','exported' => 'Approved','rejected' => 'Rejected','deleted' => 'Deleted','approvedexp' => 'Approved');
		$str = '<tr>';
		if($mode == 'create')
		{
			$str .= '<th valign="top" class="nowrap">&nbsp;</th>';
		}
		$header = 0;
		foreach($mainHeaders as $val)
		{
			if($header >= $headerCount)
			{
				if($ts_type == 'UOM' || $ts_type == 'Custom') {
						$str .= '<td valign="top" class="bold"><table><tr><td><font class=afontstylee><b>  </font></td><td class="t-r"><font class=afontstylee><label  style="margin-left:4px;" title="Billable"></label></b></span></font></td></tr></table></td>';
				}
				else{
					$str .= '<td valign="top" class="bold"><table><tr><td><font class=afontstylee><b> Hours </font></td><td class="t-r"><font class=afontstylee><label  style="margin-left:4px;" title="Billable">$</label></b></span></font></td></tr></table></td>';
				}			
			}
			else
			{
				$str .= '<td valign="top" class="nowrap">&nbsp;</td>';
			}
			$header++;
		}
		if(trim($mode) != 'pending' && trim($mode) !='errejected' && trim($mode) !='erer' && trim($mode) !='create' && trim($mode) !='Saved' && trim($mode) != '' && trim($mode)!= 'backup') {
			$str .= '<th valign="top" class="nowrap"><font class=afontstylee>&nbsp;</font></th>';
			$str .= '<th valign="top" class="nowrap"><font class=afontstylee>&nbsp;</font></th>';
		  }
		$str .= '</tr>';
		
		return $str;
    }
	
    //Added ts_type for Timesheet difference
    function getTimesheetDetails($sno, $mode,$condinvoice,$conjoin,$module='',$ts_type='')
    {
		global  $accountingExport;
		$ts_con_table1  = '';
		$ts_con_column1 = '';
		$ts_con_table2 	= '';
		$conjoin	= str_replace("hrcon_jobs.","hj.",$conjoin);
		$data = array();

		//$modeArr = array('pending'=>' and pt.astatus="ER" and th.status ="ER"','approved' =>'AND pt.astatus IN ("Approved","Billed","ER") AND th.status IN ("Approved","Billed")','exported' =>'AND pt.astatus IN ("Approved","Billed","ER") AND th.status IN ("Approved","Billed") and th.exported_status ="YES"','deleted'=>'AND pt.astatus IN ("Deleted") and th.status IN ("Deleted")','rejected'=>'AND pt.astatus IN ("Rejected") and th.status IN ("Rejected")','backup'=>' and th.status IN ("Backup")','errejected'=>'AND pt.astatus IN ("ER","Rejected") and th.status IN ("Rejected")','erer'=>'AND pt.astatus IN ("ER","Rejected") and th.status IN ("ER")');
		
		if($accountingExport == 'Exported' && $module != 'Client' && $module != 'MyProfile' ) {
			$modeArr = array('pending'=>' and th.status ="ER"','approved' =>' AND th.status IN ("Approved","Billed") and th.exported_status !="YES"','approvedexp' =>' AND th.status IN ("Approved","Billed")','exported' =>' AND th.status IN ("Approved","Billed") and th.exported_status ="YES"','deleted'=>' and th.status IN ("Deleted")','rejected'=>' and th.status IN ("Rejected")','backup'=>' and th.status IN ("Backup")','errejected'=>' and th.status IN ("Rejected")','erer'=>' and th.status IN ("ER")');
		} else {
				$modeArr = array('pending'=>' and th.status ="ER"','approved' =>' AND th.status IN ("Approved","Billed") ','exported' =>' AND th.status IN ("Approved","Billed") and th.exported_status ="YES"','deleted'=>' and th.status IN ("Deleted")','rejected'=>' and th.status IN ("Rejected")','backup'=>' and th.status IN ("Backup")','errejected'=>' and th.status IN ("Rejected")','erer'=>' and th.status IN ("ER")','approvedexp' =>' AND th.status IN ("Approved","Billed")');

			}

		if($ts_type == 'UOM') {//For getting the rates in query with rate types
			$ts_con_table1 = " (select * from hrcon_jobs LEFT JOIN (SELECT asgnid,GROUP_CONCAT( CONCAT_WS( '^^', ratemasterid, period ) SEPARATOR '&&' ) AS mulrates 
						FROM `multiplerates_assignment` 
							WHERE ratetype = 'billrate' AND asgn_mode = 'hrcon' AND status = 'Active' AND rate!='' AND ( (rate>0 AND ratemasterid IN ('rate2','rate3')) OR  (rate>1 AND ratemasterid IN ('rate1')) OR (rate>0 AND ratemasterid NOT IN ('rate1','rate2','rate3'))) GROUP BY asgnid)
						multi_rates ON multi_rates.asgnid=hrcon_jobs.sno ) ";
			$ts_con_column1 = " ,hj.mulrates ";

		} elseif($ts_type == 'Custom') {//For getting the rates in query with rate types
			$ts_con_table1 = " (select * from hrcon_jobs LEFT JOIN (SELECT asgnid,GROUP_CONCAT( CONCAT_WS( '^^', ratemasterid, period ) SEPARATOR '&&' ) AS mulrates 
						FROM `multiplerates_assignment` 
							WHERE ratetype = 'billrate' AND asgn_mode = 'hrcon' AND status = 'Active' AND rate!='' AND ( (rate>0 AND ratemasterid IN ('rate2','rate3')) OR  (rate>1 AND ratemasterid IN ('rate1')) OR (rate>0 AND ratemasterid NOT IN ('rate1','rate2','rate3'))) GROUP BY asgnid)
						multi_rates ON multi_rates.asgnid=hrcon_jobs.sno ) ";
			$ts_con_column1 = " ,hj.mulrates, p1.fname, p1.lname, pt.sno as parid, th.rowid,CONCAT(ct.type_name,IF(ct.code IS NULL OR ct.code ='','',CONCAT(' (',ct.code,')'))) AS typeName,ct.sno AS typeSno ";
			
			$ts_con_table2 = " LEFT JOIN person_info p1 ON p1.sno = th.person_id AND p1.status !='Backup' ";
			$ts_con_table3 = " LEFT JOIN custom_type ct ON ct.sno = th.cust_type";
		} else {
			$ts_con_table1 = " hrcon_jobs ";
		}

		$exported_condition = " ,u1.name as exported_user,".tzRetQueryStringDTime('th.exported_time','DateTime','/')." AS exported_time";

		if($sno != 0)

			$sql = "SELECT count(*),th.assid, th.client, sc.cname, GROUP_CONCAT(th.sno) as sno, 
			hj.sno as asgnsno, th.sdate, th.type, th.username, th.status, hj.project,
			".tzRetQueryStringDate('pt.sdate','Date','/')." AS pstartdate,
			DATE_FORMAT( pt.edate, '%m/%d/%Y' ) AS penddate,pt.notes, pt.ts_multiple,el.name, 
			".tzRetQueryStringDate('th.edate','Date','/')." AS enddate, DATE_FORMAT( th.sdate, '%W' ) AS weekday,".tzRetQueryStringDate('th.sdate','Date','/')." AS startdate, 
			".tzRetQueryStringDTime('pt.stime','DateTime24Sec','/')." AS starttimedate,
			GROUP_CONCAT( DISTINCT CONCAT( hourstype, '|', hours, '|', billable ) ) AS time_data, 
			SUM( th.hours ) AS sumhours, th.classid,th.auser, u.name,
			".tzRetQueryStringDTime('th.approvetime','DateTime24','/')." AS approvetime, pt.issues,pt.astatus, pt.pstatus,pt.atime, pt.ptime, pt.puser,pt.notes, u.type as utype,th.payroll,th.task,DATE_FORMAT( th.edate, '%W' ) AS eweekday ".$ts_con_column1." ".$exported_condition."
			FROM par_timesheet pt INNER JOIN timesheet_hours th ON pt.sno = th.parid LEFT JOIN ".$ts_con_table1." AS hj ON th.assid = hj.pusername LEFT JOIN staffacc_cinfo sc ON th.client = sc.sno  INNER JOIN emp_list el ON el.username = pt.username
			LEFT JOIN users u ON u.username = th.auser LEFT JOIN users u1 ON u1.username = th.exported_user
			".$conjoin."  ".$ts_con_table2." ".$ts_con_table3."
			WHERE th.parid = '".$sno."'  ".$modeArr[$mode]." ".$condinvoice." and th.username = pt.username GROUP BY th.rowid";

		else

			$sql = "SELECT count(*),th.assid, th.client, sc.cname, GROUP_CONCAT(th.sno) as sno, hj.sno as asgnsno, th.sdate, th.type, th.username, th.status, hj.project,".tzRetQueryStringDate('pt.sdate','Date','/')." AS pstartdate,DATE_FORMAT( pt.edate, '%m/%d/%Y' ) AS penddate,pt.notes, pt.ts_multiple,el.name, ".tzRetQueryStringDate('th.edate','Date','/')." AS enddate, DATE_FORMAT( th.sdate, '%W' ) AS weekday,".tzRetQueryStringDate('th.sdate','Date','/')." AS startdate, ".tzRetQueryStringDTime('pt.stime','DateTime24Sec','/')." AS starttimedate,
			GROUP_CONCAT( DISTINCT CONCAT( hourstype, '|', hours, '|', billable) ) AS time_data, SUM( th.hours ) AS sumhours, th.classid,th.auser, u.name,".tzRetQueryStringDTime('th.approvetime','DateTime24','/')." AS approvetime,pt.issues,pt.astatus,pt.pstatus,pt.atime,pt.ptime,pt.puser,pt.notes,u.type as utype,th.payroll,th.task,DATE_FORMAT( th.edate, '%W' ) AS eweekday ".$ts_con_column1." ".$exported_condition."
			FROM par_timesheet pt INNER JOIN timesheet_hours th ON pt.sno = th.parid LEFT JOIN ".$ts_con_table1." AS hj ON th.assid = hj.pusername LEFT JOIN staffacc_cinfo sc ON th.client = sc.sno INNER JOIN emp_list el ON el.username = pt.username
			LEFT JOIN users u ON u.username = th.auser  LEFT JOIN users u1 ON u1.username = th.exported_user ".$conjoin." ".$ts_con_table2." ".$ts_con_table3."
			WHERE 1=1  ".$modeArr[$mode]." ".$condinvoice." and th.username = pt.username GROUP BY th.rowid";
		$result = $this->mysqlobj->query($sql,$this->db);
		
		while($row = $this->mysqlobj->fetch_array($result))
		{
			$data[] = $row;
		}
		
		return $data;
    }
    //Function for getting the tooltip of repective rate types
    function getTooltip($rateid){
		$tooltip ='';
		switch($rateid){
				
			case 'UOM_DAY':
				$tooltip = 'Day';
				break;
			case 'UOM_MILE':
				$tooltip = 'Miles';
				break;
			case 'UOM_UNIT':
				$tooltip = 'Units';
				break;
			default: 
				$tooltip = 'Hours';				
		}
		return $tooltip;
    }
    
	
    function getRatevalues($data, $rateCount, $alldata,$multi_rates ='', $ts_type='', $print='', $mode = '',$module='',$Cval='',$rowid='',$parid='')
    {

		$multiRateset = array();$tooltip='';
		//Multiple rates array START
		if($multi_rates !=''){
			
			$multipleRates = explode('&&',$multi_rates);
			for($p=0;$p<count($multipleRates);$p++){
				$getType = explode('^^',$multipleRates[$p]);
				$multiRateset[$getType[0]] = $getType[1];
			}
			
		}
		//Multiple rates array END
		foreach($alldata as $val)
		{
			   $usernamedb = $val['username'];
			  $servicedateto = $val['penddate'];
			   $servicedate = $val['pstartdate'];
		}
		$prev_ts_value = $ts_type;
		$this->getAssignments($usernamedb, '', $servicedate, $servicedateto, '0',$module,'','',$Cval);
		if(count($multiRateset) > 0 && $multi_rates !=''){
			$ts_type = 'UOM';
		}
		$ratesArr =     $this->getRateTypesForAllAsgnnames($this->assignments,'',$ts_type);
		//$ratesArr = $this->getRateTypesForAllAsgn($asgnsnoarr);
		$allratearry =explode(",", $data);
		$RateTypes = $this->getRateTypes();
		
		foreach($RateTypes as $key=>$val1)
		{
			if(!in_array($val1['rateid'], $ratesArr))
			{
			unset($RateTypes[$key]);
			}
		}
		
		$i=0;
		foreach($RateTypes as $typekey => $typeval)
		{
			$i=0;
			foreach($allratearry as $allratekey => $allrateval)
			{
				$eachrate =explode("|",$allratearry[$allratekey]);
				if(trim($typeval['rateid']) == trim($eachrate[0]))
				{
					$i=1;
					break;
				}
			}
			if(count($multiRateset) > 0 && $multi_rates !=''){//For getting the tooltip for respective rate types
				$ttip = $this->getTooltip($multiRateset[$eachrate[0]]);
				$tooltip = " title='".$ttip."' ";
			}
			if($i==1 && in_array($typeval['rateid'], $ratesArr))
			{
				$st = "<table cellspacing=0 cellpadding=0><tr><td ".$tooltip."><font class=afontstylee>".number_format($eachrate[1],2)."</font></td><td style='white-space: nowrap;'><font class=afontstylee>";
				if(($eachrate[2] == 'Yes' || $eachrate[2] != 'No' ) && $eachrate[2] != '') {  
					if ($module == 'Client' || $module =='MyProfile') {
						$st .= "<input type='checkbox' checked='checked' disabled='disabled' style='margin:0px 5px;display:none;'/>";
					}else{
						$st .= "<input type='checkbox' checked='checked' disabled='disabled' style='margin:0px 5px;'/>";
					}
					
				} else { 

					if ($module == 'Client' || $module =='MyProfile' ) {
						$st .= "<input type='checkbox' disabled='disabled' style='margin:0px 5px;display:none;'/>";
					}else{
						$st .= "<input type='checkbox' disabled='disabled' style='margin:0px 5px;'/>";
					} 
				}
				if(THERAPY_SOURCE_ENABLED == 'Y' && $prev_ts_value =='Custom'){
					$person_notes_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachedNoteDetails']['parid_'.$parid]['tsrowid_'.$rowid];
					$notes = '';
					if (count($person_notes_arry)>0) {
						if (!empty($person_notes_arry['tssno_'.$eachrate[5]])) {
							$notes = $person_notes_arry['tssno_'.$eachrate[5]];
							//$person_notes = $person_notes_arry['tssno_'.$eachrate[5]].'|Y';
							$eachrate[3] = 'Y';
						}else{
							$person_attach_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachmentsCountDetails']['parid_'.$parid]['tsrowid_'.$rowid];
							if (count($person_attach_arry)>0) {
								if (!empty($person_attach_arry['tssno_'.$eachrate[5]])) {
										$eachrate[3] = 'Y';
								}
							}
						}
					}else{
						$person_attach_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachmentsCountDetails']['parid_'.$parid]['tsrowid_'.$rowid];
						if (count($person_attach_arry)>0) {
							if (!empty($person_attach_arry['tssno_'.$eachrate[5]])) {
									$eachrate[3] = 'Y';
							}
						}
					}
					if($eachrate[3] == 'Y' && empty($print))
					{
						//$notes = $this->gettsnotes($eachrate[5], $mode);
						if(!empty($notes)){
							$notestr = stripslashes(substr($notes, 0, 100)).'...';
						}else{
							$notestr = 'No notes found';
						}
						
						$st .= "<a href='#' class='tooltip'><i class='fa fa-info-circle'></i>
						<span><table height='80' width='150' class='notestooltiptable'><tr><td class='notestooltip'>".$notestr."</td></tr></table></span></a>";
						$person_attach_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachmentsCountDetails']['parid_'.$parid]['tsrowid_'.$rowid];
						$person_attach1 = 'N|';
						if (count($person_attach_arry)>0) {
							if (!empty($person_attach_arry['tssno_'.$eachrate[5]])) {
								$person_attach1 = 'Y|'.$person_attach_arry['tssno_'.$eachrate[5]];
							}
						}
						//$person_attach1 = $this->getTsRateAttachmentsCount($eachrate[5], $mode);
						$person_attach = explode('|', $person_attach1);
						if ($person_attach[0] == "Y" && $person_attach[1] !="") {
							$st.='<span class="CustomAttachments" onclick=javascript:openCustomAttachments('.$parid.','.$eachrate[5].','.$rowid.'); title="Attachments"><i class="fa fa-paperclip"></i></span>';
						}
					}
					elseif($eachrate[3] == 'N'){
						$st .= "<div style='display: inline-block;width: 14px;'>&nbsp;</div>";
					}
				}

				$st .= "</td></tr></table>";
				$s .= '<td valign="top">'.$st.'</td>';					
			}
			else
			{
				$s .= '<td>&nbsp;</td>';
			}
			  
		}
		
		return $s;
    }
    
    function buildRow($data, $rowid, $rateCount, $mode, $module='', $alldata='', $print='', $ts_type='',$Cval='')
    {
		$multi_rates ='';
		$arrMode = array('approved' => 'Approved','exported' => 'Approved','rejected' => 'Rejected','deleted' => 'Deleted');
		
		$class = $this->getClasses(" AND sno = $data[classid]");
		$str = '';
		$str .= '<tr>';
		$data['cname'] = stripslashes($data['cname']);
		
		////////////////////////////// Check box ////////////////
		if($print===''){
			if($module=='MyProfile'){
			$str .= '<td valign="top"><input type="checkbox" onclick="chk_clearTop_TimeSheet()" value="'.$data['sno'].'" id="chk'.$rowid.'" name="auids[]" checked="checked"  class="cb-element" style="display:none;"></td>';
			}else{
				if($mode=='approved' || $mode=='exported' || $mode=='deleted'){
					$str .= '<td valign="top"><input type="checkbox" onclick="chk_clearTop_TimeSheet()" value="'.$data['sno'].'" id="chk'.$rowid.'" name="auids[]" checked="checked"  class="cb-element" style="display:none;"></td>';
				}else{
					$str .= '<td valign="top"><label class="container-chk"><input type="checkbox" onclick="chk_clearTop_TimeSheet()" value="'.$data['sno'].'" id="chk'.$rowid.'" name="auids[]" checked="checked"  class="cb-element" "'.$style.'"><span class="checkmark"></span></label></td>';
				}
				
			}
		}

		/*
			This query is used to get the Shift Name based on the assignment Id
		*/
		$shift_Name ='';
		$selectShiftname = "SELECT ss.shiftname FROM shift_setup ss,hrcon_jobs hs WHERE ss.sno = hs.shiftid AND hs.pusername='".$data['assid']."'";
		$resultShiftName = mysql_query($selectShiftname,$this->db);
		$rowShiftname = mysql_fetch_row($resultShiftName);
		if ($rowShiftname[0] !="") {
			$shift_Name = ' - '.$rowShiftname[0];
		}
		/////////////////////////// Dates //////////////////////
		 if($data['enddate'] !='00/00/0000')
		$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.$data['startdate'].' - '.$data['enddate'].'</font></td>';
		else
		$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.$data['startdate'].' '.$data['weekday'].'</font></td>';
		///////////////////////////// Assignment /////////////////////////////
			if($print===''){
				$str .= '<td width="28%" align="left" style="white-space:inherit !important;word-break:break-all;"><span class="nowrap" ><font class=afontstylee>'.stripslashes($data['cname']).' ('.$data['assid'].') - '.$data['project'].$shift_Name.'</span><br/><b>Task Details :</b> '.WrapText(html_tls_specialchars(stripslashes($data['task'])),60,'');
				$str .='</font></td>';
			}else{
				$str .= '<td width="28%" align="left" style="white-space:inherit !important;word-break:break-all;"><span class="nowrap" ><font class=afontstylee>'.stripslashes($data['cname']).' ('.$data['assid'].') - '.$data['project'].$shift_Name.'</span><br/><font class=afontstylee><b>Task Details:</b>'.WrapText(html_tls_specialchars(stripslashes($data['task'])),60,'');
				$str .='</font></td>';
			}
		/////////////////////////// PERSONS ////////////////////////////////
		if(THERAPY_SOURCE_ENABLED =='Y' && $ts_type == 'Custom') {
			$person_notes_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachedNoteDetails']['parid_'.$data['parid']]['tsrowid_'.$data['rowid']];
			$person_notes = '|N';
			if (count($person_notes_arry)>0) {
				if (!empty($person_notes_arry['tssno_0'])) {
					$person_notes = $person_notes_arry['tssno_0'].'|Y';
				}
			}
			$person_notes = $this->getpersonnotes($data['parid'], $data['rowid'], $mode);
			$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.ucfirst($data['fname']).' '.ucfirst($data['lname']).'</font>';    	
			if(empty($print) && (!empty($data['fname']) || !empty($data['lname']))){
				$noteArr = explode('|', $person_notes);
				if($noteArr[1] == 'Y'){
					if($noteArr[0] != ''){
						$person_notes = substr($noteArr[0], 0, 100).'...';
					}else{
						$person_notes = 'No notes found';
					}						
					$str .= "<a href='#' class='tooltip'><i class='fa fa-info-circle'></i><span><table height='80' width='150' class='notestooltiptable'><tr><td class='notestooltip'>".
					$person_notes."</td></tr></table></span></a>";						
				}
				$person_attach_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachmentsCountDetails']['parid_'.$data['parid']]['tsrowid_'.$data['rowid']];
				$person_attach1 = 'N|';
				if (count($person_attach_arry)>0) {
					if (!empty($person_attach_arry['tssno_0'])) {
						$person_attach1 = 'Y|'.$person_attach_arry['tssno_0'];
					}
				}
				//$person_attach1 = $this->getpersonAttachmentsCount($data['parid'], $data['rowid'], $mode);
				$person_attach = explode('|', $person_attach1);
				if ($person_attach[0] == "Y" && $person_attach[1] !="") {
					$str.='<span class="CustomAttachments" onclick=javascript:openCustomAttachments("'.$data["parid"].'","0","'.$data["rowid"].'","'.$mode.'"); title="Attachments"><i class="fa fa-paperclip"></i></span>';
				}	
			}
						
			$str .= '</td>';
			$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.ucfirst($data['typeName']).'</font></td>';
		} elseif(MANAGE_CLASSES == 'Y') { 
			/////////////////////////// Classes 		////////////////////////////////
			$str .= '<td class="nowrap"><font class=afontstylee>'.$class[0]['classname'].'</font></td>';
		}
		/////////////////////// Rate types ///////////////
			
		// To handle a use case where there are multiple values for single ratetype and rowid is also same		
		$data['time_data'] = $this->getDetailsBySnos($data['sno'], $ts_type, $mode);
		//////////////////////////////////////////////////
		if(array_key_exists('mulrates',$data)){
			$multi_rates = $data['mulrates'];
		}
		$str .= $this->getRatevalues($data['time_data'], $rateCount, $alldata , $multi_rates, $ts_type, $print, $mode,$module,$Cval,$data["rowid"],$data["parid"]);
			if($mode != 'pending' && $mode !='errejected' && $mode !='erer')
			{
			
				if($mode == 'approved' || $mode=='backup' || $mode=='exported') { 
					if($data['utype']=="cllacc" && $data['auser']!="")
					{
						if($data['status']=="Approved" || $data['status']=="Billed")
						{
							if($data['status'] != "Billed" && $data['payroll'] != '')
							{
								$disSource="Self Svc (".$data['name'].") (Paid)";
							}
							elseif($data['status'] == "Billed" && $data['payroll'] != '') 
							{
								$disSource="Self Svc (".$data['name'].") (Billed/Paid)";
							}
							elseif($data['status'] == "Billed" && $data['payroll'] == '') 
							{
								$disSource="Self Svc (".$data['name'].") (Billed)";
							}
							elseif($data['status'] != "Billed" && $data['payroll'] == '') 
							{
								$disSource="Self Svc (".$data['name'].")";
							}
						}
						if($data['status']=="Rejected")
						$disSource="Rejected (".$data['name'].")";
					}
					else if($data['utype']!="cllacc" && $data['auser']!="")
					{
						if($data['status']=="Approved" || $data['status']=="Billed")
						{
							if($data['status'] != "Billed" && $data['payroll'] != '')
							{
								$disSource="Accounting (".$data['name'].") (Paid)";
							}
							elseif($data['status'] == "Billed" && $data['payroll'] != '') 
							{
								$disSource="Accounting (".$data['name'].") (Billed/Paid)";
							}
							elseif($data['status'] == "Billed" && $data['payroll'] == '') 
							{
								$disSource="Accounting (".$data['name'].") (Billed)";
							}
							elseif($data['status'] != "Billed" && $data['payroll'] == '') 
							{
								$disSource="Accounting (".$data['name'].")";
							}
						}                        
						if($data['status']=="Rejected")
						$disSource="Rejected (".$data['name'].")";

					} else 
					$disSource = $data['name'];
					$data['name'] =$disSource;
				}
				if(trim($mode) != 'pending' && trim($mode) !='errejected' && trim($mode) !='erer' && trim($mode) !='create' && trim($mode) !='Saved' && trim($mode) != '' && trim($mode)!= 'backup')
				{
					$str .= '<td  class="nowrap" valign="top"><font class=afontstylee>'.$data['name'].'</font></th>';
					$str .= '<td  class="nowrap" valign="top"><font class=afontstylee>'.$data['approvetime'].'</font></td>';
				}
				if($mode == 'exported') 
				{
					$str .= '<td  class="nowrap" valign="top"><font class=afontstylee>'.$data['exported_user'].'</font></td>';
					$str .= '<td  class="nowrap" valign="top"><font class=afontstylee>'.$data['exported_time'].'</font></td>';
				}
			}	
		$str .= '</tr>';
		return $str;
    }
    
    /*Function to get the sum of miles,units,hours,days separately*/
    function getUOMSumRowPrint($data,$headerArr='', $ts_type = ''){
	
		$rate_totalsort_arr = array();
		$rate_totalunit_arr = array();
		$ratetypes_ids = array();
		$rate_inc ='';
		
		$headerArr_rates=array();
		$headerArr_rates = $headerArr;
		array_shift($headerArr_rates);
		$header_rates= implode(',',$headerArr_rates);
		$header_rates=str_replace(",","','",$header_rates);
		
		$rate_que = "select sno,rateid,name from multiplerates_master where name IN ('".$header_rates."')";
		$rate_que_sel=$this->mysqlobj->query($rate_que,$this->db);
	
		while($rate_que_myrow=$this->mysqlobj->fetch_array($rate_que_sel))
		{
			$rate_que_myrow['rateid'] =substr($rate_que_myrow['rateid'],4);
			$ratetypes_ids[$rate_que_myrow['rateid']] = $rate_que_myrow['name'];			
		}
		//$count = $data[0][0];
		$count = count($data);
		$sum = 0;
		$uomDay_sum = 0;
		$uomMile_sum = 0;
		$uomUnit_sum = 0;
		$hourSum = 0;
		$hr_inc = 0;$day_inc = 0;$mile_inc = 0;$unit_inc = 0;
		for($i = 0; $i < $count; $i++)
		{
			$rateArr=array();
			$rate_unit_arr = array();
			
			$ratesSel = explode(',',$data[$i]['time_data']);
			
			
			//Multiple rates array START
			if($data[$i]['mulrates'] !=''){
				$multipleRates = explode('&&',$data[$i]['mulrates']);
				for($p=0;$p<count($multipleRates);$p++){
					$getType = explode('^^',$multipleRates[$p]);
					$rate_unit_arr[$getType[0]]['rate_type'] = $getType[1];
					
				}
			}
			for($r=0;$r<count($ratesSel);$r++){
				$rateVal = explode('|',$ratesSel[$r]);
				$rateArr[$rateVal[0]] = $rateVal[1];
				$rate_unit_arr[$rateVal[0]]['units_cover'] = $rateVal[1];
			}
			
			if($rate_unit_arr){
				foreach($rate_unit_arr as $rate_key=>$rate_val){
					
					if($rate_val['rate_type'] == 'UOM_MILE'){
						$rate_totalunit_arr[$rate_key]['total_miles'] += $rate_val['units_cover'];
						$mile_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_DAY'){
						$rate_totalunit_arr[$rate_key]['total_days'] += $rate_val['units_cover'];
						$day_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_UNIT'){
						$rate_totalunit_arr[$rate_key]['total_units'] += $rate_val['units_cover'];
						$unit_inc++;
					}
					else {
						$rate_totalunit_arr[$rate_key]['total_hours'] += $rate_val['units_cover'];
						$hr_inc++;
					}
					$rate_inc =substr($rate_key,4);
					$rate_totalsort_arr[$rate_inc] = $rate_totalunit_arr[$rate_key];
				}
				
			}
			
			$sum = $sum + $data[$i]['sumhours'];
		}
		foreach($ratetypes_ids as $ratetypes_ids_key=>$ratetypes_ids_val){
			if(!array_key_exists($ratetypes_ids_key,$rate_totalsort_arr)){
				$rate_totalsort_arr[$ratetypes_ids_key]=array();
			}
		}
		ksort($rate_totalsort_arr);
			
		///////////////////////////// Total hours ////////////////////
		echo '<tr>';
		echo '<td></td>';
		if(in_array('Class',$headerArr)){
			echo '<td></td>';
		}
		elseif($ts_type == 'Custom'){
			echo '<td></td><td></td>';
		}
		echo '<td align=right>';
		if($hr_inc != 0){
			echo '<div><font class=afontstylee >Total Hours: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($day_inc != 0){
			echo '<div><font class=afontstylee >Total Days: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($mile_inc != 0){
			echo '<div><font class=afontstylee >Total Miles: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($unit_inc != 0){
			echo '<div><font class=afontstylee >Total Units: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		echo '</td>';
		foreach($rate_totalsort_arr as $total_key => $total_val){
			echo '<td align=right>';
			if($total_val['total_hours'] != 0){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_hours'],2,'.','').'</font></div>';
			}
			else if($hr_inc != 0 ){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
			}
			if($total_val['total_days'] != 0){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_days'],2,'.','').'</font></div>';
			}
			else if($day_inc != 0 ){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
			}
			if($total_val['total_miles'] != 0){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_miles'],2,'.','').'</font></div>';
			}
			else if($mile_inc != 0 ){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
			}
			if($total_val['total_units'] != 0){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_units'],2,'.','').'</font></div>';
			}
			else if($unit_inc != 0){
				echo '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
			}
			echo '</td>';
		}
		echo '</tr>';
		echo '<tr><td></td>';
		if(in_array('Class',$headerArr)){
			echo '<td></td>';
		$tu =3;
		}elseif($ts_type == 'Custom'){
			echo '<td></td><td></td>';
		$tu =3;
		}else{
			$tu =2;
		}
		echo '<td align=right><div><font class=afontstylee >Grand Total: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div></td>';
		for($tu;$tu<count($headerArr)-1;$tu++){
			echo '<td></td>';
		}
		echo '<td align=right><div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($sum,2,'.','').'</font></div></td></tr>';
		
		
	}
    function getHoursSumRowPrint($data)
    {
		//$count = $data[0][0];
		$count = count($data);
		$sum = 0;
		for($i = 0; $i <= $count; $i++)
		{
			$sum = $sum + $data[$i]['sumhours'];
		}
		///////////////////////////// Total hours ////////////////////
		echo '<tr><td colspan="1">';
		echo '<font class=afontstyle>&nbsp;</font></td>';
		echo '<td align=right><font class=afontstylee >Total Hours: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></td>';
		echo '<td valign="top"><font class=afontstylee>'.number_format($sum,2,'.','').'</font></td>';
		echo '<td><font class=afontstylee>&nbsp;</font></td></tr>';
		//echo   $str;
    }
	
	
	function getHoursSumRowEmail($data)
    {
		//$count = $data[0][0];
		$sum_hours = '';
		$count = count($data);
		$sum = 0;
		for($i = 0; $i <= $count; $i++)
		{
			$sum = $sum + $data[$i]['sumhours'];
		}
		///////////////////////////// Total hours ////////////////////
		$sum_hours .= '<tr><td colspan="1">';
		$sum_hours .=  '<font class=afontstyle>&nbsp;</font></td>';
		$sum_hours .=  '<td align=right><font class=afontstylee >Total Hours: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></td>';
		$sum_hours .=  '<td valign="top"><font class=afontstylee>'.number_format($sum,2,'.','').'</font></td>';
		$sum_hours .=  '<td><font class=afontstylee>&nbsp;</font></td></tr>';
		//echo   $str;
		return $sum_hours;
    }
    function getUOMSumRowEmail($data,$headerArr='', $ts_type = '')
    {
		$rate_totalsort_arr = array();
		$rate_totalunit_arr = array();
		$ratetypes_ids = array();
		$rate_inc ='';
		
		$sum_hours ='';
		
		$headerArr_rates=array();
		$headerArr_rates = $headerArr;
		array_shift($headerArr_rates);
		$header_rates= implode(',',$headerArr_rates);
		$header_rates=str_replace(",","','",$header_rates);
		
		$rate_que = "select sno,rateid,name from multiplerates_master where name IN ('".$header_rates."')";
		$rate_que_sel=$this->mysqlobj->query($rate_que,$this->db);
	
		while($rate_que_myrow=$this->mysqlobj->fetch_array($rate_que_sel))
		{
			$rate_que_myrow['rateid'] =substr($rate_que_myrow['rateid'],4);
			$ratetypes_ids[$rate_que_myrow['rateid']] = $rate_que_myrow['name'];			
		}
		//$count = $data[0][0];
		$count = count($data);
		$sum = 0;
		$uomDay_sum = 0;
		$uomMile_sum = 0;
		$uomUnit_sum = 0;
		$hourSum = 0;
		$hr_inc = 0;$day_inc = 0;$mile_inc = 0;$unit_inc = 0;
		for($i = 0; $i < $count; $i++)
		{
			$rateArr=array();
			$rate_unit_arr = array();
			
			$ratesSel = explode(',',$data[$i]['time_data']);
			
			
			//Multiple rates array START
			if($data[$i]['mulrates'] !=''){
				$multipleRates = explode('&&',$data[$i]['mulrates']);
				for($p=0;$p<count($multipleRates);$p++){
					$getType = explode('^^',$multipleRates[$p]);
					$rate_unit_arr[$getType[0]]['rate_type'] = $getType[1];
					
				}
			}
			for($r=0;$r<count($ratesSel);$r++){
				$rateVal = explode('|',$ratesSel[$r]);
				$rateArr[$rateVal[0]] = $rateVal[1];
				$rate_unit_arr[$rateVal[0]]['units_cover'] = $rateVal[1];
			}
			
			if($rate_unit_arr){
				foreach($rate_unit_arr as $rate_key=>$rate_val){
					
					if($rate_val['rate_type'] == 'UOM_MILE'){
						$rate_totalunit_arr[$rate_key]['total_miles'] += $rate_val['units_cover'];
						$mile_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_DAY'){
						$rate_totalunit_arr[$rate_key]['total_days'] += $rate_val['units_cover'];
						$day_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_UNIT'){
						$rate_totalunit_arr[$rate_key]['total_units'] += $rate_val['units_cover'];
						$unit_inc++;
					}
					else {
						$rate_totalunit_arr[$rate_key]['total_hours'] += $rate_val['units_cover'];
						$hr_inc++;
					}
					$rate_inc =substr($rate_key,4);
					$rate_totalsort_arr[$rate_inc] = $rate_totalunit_arr[$rate_key];
				}
				
			}
			
			$sum = $sum + $data[$i]['sumhours'];
		}
		foreach($ratetypes_ids as $ratetypes_ids_key=>$ratetypes_ids_val){
			if(!array_key_exists($ratetypes_ids_key,$rate_totalsort_arr)){
				$rate_totalsort_arr[$ratetypes_ids_key]=array();
			}
		}
		ksort($rate_totalsort_arr);
			
		///////////////////////////// Total hours ////////////////////
		$sum_hours .= '<tr><td colspan="1">';
		$sum_hours .= '<font class=afontstyle>&nbsp;</font></td><td></td>';
		if(in_array('Class',$headerArr)){
			$sum_hours .= '<td></td>';
		}elseif($ts_type == 'Custom'){			
			$sum_hours .= '<td></td><td></td>';
		}
		$sum_hours .= '<td align=right>';
		if($hr_inc != 0){
			$sum_hours .= '<div><font class=hfontstyle >Total Hours: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		if($day_inc != 0){
			$sum_hours .= '<div><font class=hfontstyle >Total Days: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		if($mile_inc != 0){
			$sum_hours .= '<div><font class=hfontstyle >Total Miles: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		if($unit_inc != 0){
			$sum_hours .= '<div><font class=hfontstyle >Total Units: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		$sum_hours .= '</td>';
		foreach($rate_totalsort_arr as $total_key => $total_val){
			$sum_hours .= '<td align=right>';
			
			if($total_val['total_hours'] != 0){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >'.number_format($total_val['total_hours'],2,'.','').'</font></div>';
			}
			else if($hr_inc != 0 ){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >0.00</font></div>';
			}
			if($total_val['total_days'] != 0){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >'.number_format($total_val['total_days'],2,'.','').'</font></div>';
			}
			else if($day_inc != 0 ){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >0.00</font></div>';
			}
			if($total_val['total_miles'] != 0){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >'.number_format($total_val['total_miles'],2,'.','').'</font></div>';
			}
			else if($mile_inc != 0 ){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >0.00</font></div>';
			}
			if($total_val['total_units'] != 0){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >'.number_format($total_val['total_units'],2,'.','').'</font></div>';
			}
			else if($unit_inc != 0){
				$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >0.00</font></div>';
			}
			
			$sum_hours .= '</td>';
		}
		$sum_hours .= '</tr>';
		$sum_hours .= '<tr><td colspan="1"></td><td></td>';
		if(in_array('Class',$headerArr)){
			$sum_hours .= '<td></td>';
			$tu =4;
		}elseif($ts_type == 'Custom'){
			$sum_hours .= '<td></td><td></td>';
			$tu =4;
		}else{
			$tu =3;
		}
		$sum_hours .= '<td align=right><div><font class=hfontstyle >Grand Total: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div></td>';
		for($tu;$tu<count($headerArr)-1;$tu++){
			$sum_hours .= '<td></td>';
		}
		$sum_hours .= '<td align=right><div style="text-align:right;padding-right: 25px;"><font class=hfontstyle >'.number_format($sum,2,'.','').'</font></div></td></tr>';
		
		return $sum_hours;
	
    }
	function getHoursSumRow($data)
    {
	
	$count = count($data);
	$sum = 0;
	for($i = 0; $i <= $count; $i++)
	{
	    $sum = $sum + $data[$i]['sumhours'];
	}
	///////////////////////////// Total hours ////////////////////
	$str .= '<tr class=custTime><td>';
	$str .= '<font class=afontstyle>&nbsp;</font></td>';
	$str .= '<td align=right><font class=hfontstyle >Total Hours: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></td>';
	$str .= '<td valign="top"><font class=hfontstyle>'.number_format($sum,2,'.','').'</font></td>';
	$str .= '<td><font class=hfontstyle>&nbsp;</font></td></tr>';
	return $str;
    }
	
	
    /*Function to get the sum of miles,units,hours,days individually rates wise separately*/
    function getUOMSumRow($data, $headerArr='', $ts_type = ''){
		$rate_totalsort_arr = array();
		$rate_totalunit_arr = array();
		$ratetypes_ids = array();
		$rate_inc ='';		
		$str ='';
		$headerArr_rates=array();
		$headerArr_rates = $headerArr;
		array_shift($headerArr_rates);
		$header_rates= implode(',',$headerArr_rates);
		$header_rates=str_replace(",","','",$header_rates);
		
		$rate_que = "select sno,rateid,name from multiplerates_master where name IN ('".$header_rates."')";
		$rate_que_sel=$this->mysqlobj->query($rate_que,$this->db);
	
		while($rate_que_myrow=$this->mysqlobj->fetch_array($rate_que_sel))
		{
			$rate_que_myrow['rateid'] =substr($rate_que_myrow['rateid'],4);
			$ratetypes_ids[$rate_que_myrow['rateid']] = $rate_que_myrow['name'];			
		}
		
		$count = count($data);
		$sum = 0;
		$uomDay_sum = 0;
		$uomMile_sum = 0;
		$uomUnit_sum = 0;
		$hourSum = 0;
		$hr_inc = 0;$day_inc = 0;$mile_inc = 0;$unit_inc = 0;
		for($i = 0; $i < $count; $i++)
		{
			$rateArr=array();
			$rate_unit_arr = array();
			
			$ratesSel = explode(',',$data[$i]['time_data']);
			
			
			//Multiple rates array START
			if($data[$i]['mulrates'] !=''){
				$multipleRates = explode('&&',$data[$i]['mulrates']);
				for($p=0;$p<count($multipleRates);$p++){
					$getType = explode('^^',$multipleRates[$p]);
					$rate_unit_arr[$getType[0]]['rate_type'] = $getType[1];
					
				}
			}
			for($r=0;$r<count($ratesSel);$r++){
				$rateVal = explode('|',$ratesSel[$r]);
				$rateArr[$rateVal[0]] = $rateVal[1];
				$rate_unit_arr[$rateVal[0]]['units_cover'] = $rateVal[1];
			}
			
			if($rate_unit_arr){
				foreach($rate_unit_arr as $rate_key=>$rate_val){
					
					if($rate_val['rate_type'] == 'UOM_MILE'){
						$rate_totalunit_arr[$rate_key]['total_miles'] += $rate_val['units_cover'];
						$mile_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_DAY'){
						$rate_totalunit_arr[$rate_key]['total_days'] += $rate_val['units_cover'];
						$day_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_UNIT'){
						$rate_totalunit_arr[$rate_key]['total_units'] += $rate_val['units_cover'];
						$unit_inc++;
					}
					else {
						$rate_totalunit_arr[$rate_key]['total_hours'] += $rate_val['units_cover'];
						$hr_inc++;
					}
					$rate_inc =substr($rate_key,4);
					$rate_totalsort_arr[$rate_inc] = $rate_totalunit_arr[$rate_key];
				}
				
			}
			
			$sum = $sum + $data[$i]['sumhours'];
		}

		foreach($ratetypes_ids as $ratetypes_ids_key=>$ratetypes_ids_val){
			if(!array_key_exists($ratetypes_ids_key,$rate_totalsort_arr)){
				$rate_totalsort_arr[$ratetypes_ids_key]=array();
			}
		}
		ksort($rate_totalsort_arr);
		///////////////////////////// Total hours ////////////////////
		$str .= '<tr class=custTime><td>';
		$str .= '<font class=afontstyle>&nbsp;</font></td>';
		if(in_array('Class',$headerArr)){
			$str .= '<td></td>';
		}
		elseif($ts_type == 'Custom'){
			$str .= '<td></td><td></td>';
		}
		
		$str .= '<td align=right colspan=2 width="10%">';
		if($hr_inc != 0){
			$str .= '<div><font class=hfontstyle >Total Hours: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		if($day_inc != 0){
			$str .= '<div><font class=hfontstyle >Total Days: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		if($mile_inc != 0){
			$str .= '<div><font class=hfontstyle >Total Miles: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		if($unit_inc != 0){
			$str .= '<div><font class=hfontstyle >Total Units: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div>';
		}
		$str .= '</td>';
		foreach($rate_totalsort_arr as $total_key => $total_val){
			$str .= '<td align=left>';
			
			if($total_val['total_hours'] != 0){
				$str .= '<div><font class=hfontstyle >'.number_format($total_val['total_hours'],2,'.','').'</font></div>';
			}
				
			else if($hr_inc != 0 ){
				$str .= '<div><font class=hfontstyle >0.00</font></div>';
			}
			if($total_val['total_days'] != 0){
				$str .= '<div><font class=hfontstyle >'.number_format($total_val['total_days'],2,'.','').'</font></div>';
			}
			else if($day_inc != 0 ){
				$str .= '<div><font class=hfontstyle >0.00</font></div>';
			}
			if($total_val['total_miles'] != 0){
				$str .= '<div><font class=hfontstyle >'.number_format($total_val['total_miles'],2,'.','').'</font></div>';
			}
			else if($mile_inc != 0 ){
				$str .= '<div><font class=hfontstyle >0.00</font></div>';
			}
			if($total_val['total_units'] != 0){
				$str .= '<div><font class=hfontstyle >'.number_format($total_val['total_units'],2,'.','').'</font></div>';
			}
			else if($unit_inc != 0){
				$str .= '<div><font class=hfontstyle >0.00</font></div>';
			}
			
			$str .= '</td>';
		}
		$str .= '</tr>';
		$str .= '<tr class=custTime><td></td>';
		if(in_array('Class',$headerArr)){
			$str .= '<td></td>';
			$tu =4;
		}elseif($ts_type == 'Custom'){
			$str .= '<td></td><td></td>';
			$tu =4;
		}else{
			$tu =3;
		}
		$str .= '<td colspan=2 align=right><div><font class=hfontstyle >Grand Total: &nbsp;&nbsp;</font><font class=hfontstyle>&nbsp;</font></div></td>';
		for($tu;$tu<count($headerArr)-1;$tu++){
			$str .= '<td></td>';
		}
		$str .= '<td align=left><div><font class=hfontstyle >'.number_format($sum,2,'.','').'</font></div></td></tr>';
		
		return $str;
	}

    function getTimesheetAttachments($sno, $mode='')
    {
	$sql="select sno, name from time_attach where parid='".$sno."'";
	$result = $this->mysqlobj->query($sql,$this->db);
	$str .= '<table border="0" id="attachfiles"><tr><th colspan=2><font class=afontstylee>Attached Time Sheet File:</th><th>&nbsp;</th></tr>';
	$rowcount = 1;
	while($row = $this->mysqlobj->fetch_array($result))
	{
	    if($mode=='edit')
	    {
		$str1 = '<font class=afontstylee><a href="javascript: void(0);" onclick="delTimeAttach('.$row['sno'].', '.$sno.');">Delete file</a><font>';
	    }
	    $str .= '<tr id="'.$row['sno'].'"><td>&nbsp;</td><td><font class=afontstylee><a href="/include/downts.php?id='.$row['sno'].'">'.$row['name'].'</a>&nbsp;&nbsp;'.$str1.'</font></td></tr>';
	    $rowcount++;
	}
	$str .= '</table>';
	if( $rowcount == 1)
	$str ='';
	
	return $str;
    }
	
	function displaysubheading($sno,$mode,$module='')
	{
		global  $accountingExport;
		if($accountingExport == 'Exported' && $module !='Client' && $module !='MyProfile') {
			$modeArr = array('pending'=>' and th.status ="ER"','approved' =>' AND th.status IN ("Approved","Billed") and  th.exported_status !="YES"','exported' =>' AND  th.status IN ("Approved","Billed") and th.exported_status ="YES"','deleted'=>' AND   th.status IN ("Deleted")','rejected'=>' AND th.status IN ("Rejected")','errejected'=>' and th.status IN ("Rejected")','erer'=>' and th.status IN ("ER")','Saved'=>' and  th.status ="Saved"','approvedexp' =>' AND th.status IN ("Approved","Billed")');
		} else {
			 $modeArr = array('pending'=>' and th.status ="ER"','approved' =>' AND th.status IN ("Approved","Billed") ','exported' =>' AND  th.status IN ("Approved","Billed") and th.exported_status ="YES"','deleted'=>' and th.status IN ("Deleted")','rejected'=>' AND th.status IN ("Rejected")','errejected'=>' and th.status IN ("Rejected")','erer'=>' and th.status IN ("ER")','Saved'=>' and  th.status ="Saved"','approvedexp' =>' AND th.status IN ("Approved","Billed")');

		}
	
	 $sql = "SELECT el.name,".tzRetQueryStringDTime('pt.stime','DateTime24Sec','/')." as stimedate,".tzRetQueryStringDate('pt.sdate','Date','/')." as sdate, ".tzRetQueryStringDate('pt.edate','Date','/')." as edate,
	u.name as submited_by 
 FROM par_timesheet pt INNER JOIN timesheet_hours th ON pt.sno = th.parid INNER JOIN emp_list el ON el.username = pt.username LEFT JOIN users u ON u.username = pt.cuser
 WHERE th.parid = '".$sno."'  ".$modeArr[$mode]." and th.username = pt.username GROUP BY th.parid";
	$result = $this->mysqlobj->query($sql,$this->db);
	$row = $this->mysqlobj->fetch_array($result);
	$row['name'] = stripslashes($row['name']);
	
	$subheading_timesheet = "Timesheet for <b>".$row['name']."</b> Submitted by <b>".(($row['submited_by'] !='')?$row['submited_by']:$row['name'])."</b> on <b>".$row['stimedate']."</b>.";
		
	
	if($mode == 'pending' || $mode =='Saved') {

		$header_text	= ($mode == 'Saved') ? 'Saved&nbsp;Timesheet' : 'Submitted&nbsp;Timesheet';

		$output =  "<td colspan=2 class=titleNewPad><font class=modcaption>&nbsp;&nbsp;$header_text</font></td>
	            <td align=right class=titleNewPad><font class=afontstyle color=black>&nbsp;&nbsp;Following are <b>".$row['name']."</b> Time Sheet details from <b>".$row['sdate']."</b> to <b>".$row['edate']."</b> Submitted by <b>".(($row['submited_by'] !='')?$row['submited_by']:$row['name'])."</b> on <b>".$row['stimedate']."</b>.</font></td>";
	}
	if($mode == 'approved')
	$output =" <td colspan=2><font class=modcaption>&nbsp;&nbsp;Approved&nbsp;Timesheet</font></td>
                <td align=right><font class=afontstyle>".$subheading_timesheet."</font></td>";
	if($mode == 'deleted' || $mode == 'Deleted')
	 $output ="<td colspan=2><font class=modcaption>&nbsp;&nbsp;Deleted&nbsp;Timesheet</font></td>
                <td align=right><font class=afontstyle>".$subheading_timesheet."</font></td>";
   	if($mode == 'rejected' || $mode == 'Rejected')
	$output ="<td colspan=2><font class=modcaption>&nbsp;&nbsp;Rejected&nbsp;Timesheet</font></td>
                <td align=right><font class=afontstyle>".$subheading_timesheet."</font></td>";
	if($mode == 'exported')
	$output ="<td colspan=2><font class=modcaption>&nbsp;&nbsp;Exported&nbsp;Timesheet</font></td>
                <td align=right><font class=afontstyle>".$subheading_timesheet."</font></td>";
	
	
	$empdata ="<tr>
				<td class='titleNewPad'>
					<table width=100% cellpadding=0 cellspacing=0 border=0>
						<tr>".$output."</tr></table>
				</td>
				</tr>";
	
	   return $empdata;
 
	}
    
function displayTimesheetDetailsPrint($sno, $mode,$condinvoice ='',$conjoin='', $module='', $print=True, $invoice='',$ts_type='',$Cval='')
{
	$exportStatus = $this->getExported_status($sno);
    
	if($exportStatus == 'YES' && $invoice != '' && $mode!='approvedexp')
	{
	    $mode = 'exported';
	}
	
	$table = '<table id="grid_form" class="grid_forms">';
	echo '<table cellspacing="1" cellpadding="5" width="100%"  border=0 style="text-align:left;"> ';
	if($module=='MyProfile'){
		$chk_cond = '<input type="checkbox" id="chk" class="chk" value="check all" checked="checked" onclick="mainChkBox_ProcessedRecords()" style="display:none">';
	}else{
			
		if($mode=='approved' || $mode=='exported' || $mode=='deleted'){
			$chk_cond = '<input type="checkbox" id="chk" class="chk" value="check all" checked="checked" onclick="mainChkBox_ProcessedRecords()" style="display:none;">';
		}else{
			$chk_cond = '<input type="checkbox" id="chk" class="chk" value="check all" checked="checked" onclick="mainChkBox_ProcessedRecords()">';
		}
	}
	
	// get the timesheet details
	$data = $this->getTimesheetDetails($sno, $mode,$condinvoice,$conjoin, $module,$ts_type);

	foreach($data as $val)
	{

        $usernamedb = $val['username'];
	    $servicedateto = $val['penddate'];
        $servicedate = $val['pstartdate'];
	    
	}
	$this->getAssignments($usernamedb, '', $servicedate, $servicedateto, '0',$module,'','',$Cval);
	$ratesArr =     $this->getRateTypesForAllAsgnnames($this->assignments,'',$ts_type);

	if($print===True){
		$headerArr = array('Date', 'Assignments');
	}else{
		$headerArr = array($chk_cond, 'Date', 'Assignments');
	}
	
	if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == "Custom")
	{
		array_push($headerArr, $this->getCustomHeaders());
		array_push($headerArr, 'Type');
	} elseif(MANAGE_CLASSES == 'Y') {
		array_push($headerArr, 'Class');
	}
	
	$headerCount = count($headerArr);
	$ratetype = $this->getRateTypes();
	$rateCount = count($ratesArr);
	foreach($ratetype as $val)
	{
	    if(in_array($val['rateid'], $ratesArr))
	    {
		array_push($headerArr, $val['name']);
	    }
	}
	/////////////////////////// Main Headers ///////////////////////
	
	echo  $this->buildMainHeaders($headerArr,$mode);
	//////////////////////// Sub Headers (Hour & Billable) ////////
	echo  $this->buildSubHeaders($headerArr, $headerCount,$mode,$ts_type);
	
	
	foreach($data as $key=>$val)
	{
	    //////////////////////// Sub Headers (Hour & Billable) ////////
	    echo  $this->buildRow($val, $key, $rateCount,$mode, $module, $data,$print=True, $ts_type,$Cval);
	}
	////////////// Total hours //////////
	
	if($ts_type == 'UOM') {//UOM Timesheet for getting the differencr totals
		$this->getUOMSumRowPrint($data,$headerArr);
	} elseif($ts_type == 'Custom') {//Custom Timesheet for getting the difference totals
		$this->getUOMSumRowPrint($data,$headerArr, $ts_type);
	} else {
		$this->getHoursSumRowPrint($data);
	}
	
	if($mode != 'pending' && $mode != 'errejected' && $mode != 'erer')
	$count = count($headerArr) +2;
	else if($mode == 'pending')
	$count = count($headerArr);
	else if($mode == 'errejected' || $mode =='erer')
	$count = count($headerArr) - 1;
	//////////////////////////// Submitted date ////////////////
	echo '<tr class=hthbgcolor><td colspan='.$count.' class="nowrap"><font class=afontstylee>Submitted Date:&nbsp;<b>'.$data[0]['starttimedate'].'</font></td>';
	if($mode == 'errejected' || $mode == 'erer')
	{
	echo '<td colspan='.$count.' class="nowrap"><font class=afontstylee>'.'</font></td>';
	}
	echo '</tr>';
	echo '</table> ';
	echo '</table>';
	
	//////////////////////////// Remarks ////////////////
	if($data[0]['issues'] != '') {
	echo '<br /><font class=afontstylee><b>Remarks:</b>&nbsp;'.WrapText(html_tls_specialchars(stripslashes($data[0]['issues'])),60,'').'</font>';
	}
	
	////////////////notes/////////////////////////////////
	if($data[0]['notes'] !='' || $data[0]['notes'] !=NULL)
	echo '<br /><font class=afontstylee><b>Notes:</b>&nbsp;'.WrapText(html_tls_specialchars(stripslashes($data[0]['notes'])),60,'').'</font>';	
		echo $table;	
    }	
	
	
	function displayTimesheetDetailsEmail($sno, $mode,$condinvoice ='',$conjoin='', $module='',$ts_type='')
    {
	$table = '<div id="grid_form" class="grid_forms" style="white-space:nowrap">';
	$table .= '<table cellspacing="1" cellpadding="5" width="100%"  border=0 style="text-align:left;"> ';
		
	// get the timesheet details
	$data = $this->getTimesheetDetails($sno, $mode,$condinvoice,$conjoin,$module,$ts_type);

	foreach($data as $val)
	{

		$usernamedb = $val['username'];
		$servicedateto = $val['penddate'];
		$servicedate = $val['pstartdate'];
	   
	}
	 $this->getAssignments($usernamedb, '', $servicedate, $servicedateto, '0');
	$ratesArr =     $this->getRateTypesForAllAsgnnames($this->assignments,'',$ts_type);
	
	$headerArr = array('Date', 'Assignments');
	
	if(MANAGE_CLASSES == 'Y')
	{
	    array_push($headerArr, 'Class');
	}
	$headerCount = count($headerArr);
	$ratetype = $this->getRateTypes();
	$rateCount = count($ratesArr);
	foreach($ratetype as $val)
	{
	    if(in_array($val['rateid'], $ratesArr))
	    {
		array_push($headerArr, $val['name']);
	    }
	}
	/////////////////////////// Main Headers ///////////////////////
	
	$table .= $this->buildMainHeaders($headerArr,$mode);
	//////////////////////// Sub Headers (Hour & Billable) ////////
	$table .= $this->buildSubHeaders($headerArr, $headerCount,$mode,$ts_type);
	
	
	foreach($data as $key=>$val)
	{
	    //////////////////////// Sub Headers (Hour & Billable) ////////
		$table .= $this->buildRow($val, $key, $rateCount,$mode, $module, $data,$print=True);
	}
	////////////// Total hours //////////
	if($ts_type == 'UOM') {
		$table .= $this->getUOMSumRowEmail($data,$headerArr);
	} elseif($ts_type == 'Custom') {
		$table .= $this->getUOMSumRowEmail($data,$headerArr, $ts_type);
	} else {
		$table .= $this->getHoursSumRowEmail($data);
	}
	
	
	if($mode != 'pending' && $mode != 'errejected' && $mode != 'erer')
	$count = count($headerArr) +2;
	else if($mode == 'pending')
	$count = count($headerArr);
	else if($mode == 'errejected' || $mode =='erer')
	$count = count($headerArr) - 1;
	//////////////////////////// Submitted date ////////////////
	$table .= '<tr class=hthbgcolor><td colspan='.$count.' class="nowrap"><font class=afontstylee>Submitted Date:&nbsp;<b>'.$data[0]['starttimedate'].'</font></td>';
	if($mode == 'errejected' || $mode == 'erer')
	{
	$table .= '<td colspan='.$count.' class="nowrap"><font class=afontstylee>'.'</font></td>';
	}
	$table .='</tr>';
	$table .= '</table> ';
	$table .= '</div>';
	
	return $table;	
    }
    function getExported_status($sno)
    {	
	
	$sql = "SELECT distinct exported_status FROM timesheet_hours WHERE STATUS IN('Billed', 'Approved') and parid = ".$sno;
	$result = mysql_query($sql, $this->db);
	
	$row = mysql_fetch_row($result);
	
	return $row[0];
	
    }
   function displayTimesheetDetails($sno, $mode, $condinvoice ='', $conjoin='', $module='', $invoice='',$ts_type='',$Cval='')
    {
	$exportStatus = $this->getExported_status($sno);
    
	if($exportStatus == 'YES' && $invoice != ''&& $mode!='approvedexp')
	{
	    $mode = 'exported';
	}
	
	$table = '<table cellspacing="0" cellpadding="5" width="100%"  border=0 style="text-align:left;" class=CustomTimesheetTh> ';
	if($module=='MyProfile'){
		$chk_cond = '<input type="checkbox" id="chk" class="chk" value="check all" checked="checked" onclick="mainChkBox_ProcessedRecords()" style="display:none">';
	}else{
			
		if($mode=='approved' || $mode=='exported' || $mode=='deleted'){
			$chk_cond = '<input type="checkbox" id="chk" class="chk" value="check all" checked="checked" onclick="mainChkBox_ProcessedRecords()" style="display:none;">';
		}else{
			$chk_cond = '<label class="container-chk"><input type="checkbox" id="chk" class="chk" value="check all" checked="checked" onclick="mainChkBox_ProcessedRecords()"><span class="checkmark"></span></label>';
		}
	}
	
	// get the timesheet details
	$data = $this->getTimesheetDetails($sno, $mode,$condinvoice,$conjoin,$module,$ts_type);

	foreach($data as $val)
	{
		$usernamedb 	= $val['username'];
		$servicedateto 	= $val['penddate'];
		$servicedate 	= $val['pstartdate'];
		$timesheetsno 	= $val['sno'];
	}
	 $this->getAssignments($usernamedb, '', $servicedate, $servicedateto, '0',$module,'','',$Cval);
	$ratesArr =     $this->getRateTypesForAllAsgnnames($this->assignments,'',$ts_type);
		
	$headerArr = array($chk_cond, 'Date', 'Assignments');
	
	if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == "Custom") {
	    array_push($headerArr, $this->getCustomHeaders());
	    array_push($headerArr, 'Type');
	} elseif(MANAGE_CLASSES == 'Y') {
	    array_push($headerArr, 'Class');
	}
	$headerCount = count($headerArr);
	$ratetype = $this->getRateTypes();
	$rateCount = count($ratesArr);
	foreach($ratetype as $val)
	{
	    if(in_array($val['rateid'], $ratesArr))
	    {
		array_push($headerArr, $val['name']);
	    }
	}
	/////////////////////////// Main Headers ///////////////////////
	
	$table .= $this->buildMainHeaders($headerArr,$mode);
	//////////////////////// Sub Headers (Hour & Billable) ////////
	$table .= $this->buildSubHeaders($headerArr, $headerCount,$mode,$ts_type);
	
	if (THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == 'Custom') {
		$PersonAttachmentsDetail = $this->getPersonAttachedNoteDetails($sno,$mode);
		$PersonAttachmentsCountDetail = $this->getPersonAttachmentsCountDetails($sno,$mode);
		$this->eachrowidTotalPersonAttachmentValArr['PersonAttachmentsCountDetails']= $PersonAttachmentsCountDetail;
		$this->eachrowidTotalPersonAttachmentValArr['PersonAttachedNoteDetails']= $PersonAttachmentsDetail;
	}
	foreach($data as $key=>$val)
	{
	    //////////////////////// Sub Headers (Hour & Billable) ////////
	    $table .= $this->buildRow($val, $key, $rateCount,$mode, $module, $data, '', $ts_type,$Cval);
	}

	////////////// Total hours //////////
	if($ts_type == 'UOM'){//UOM Timesheet for sum row individually
		$table .= $this->getUOMSumRow($data,$headerArr);
	}elseif($ts_type == 'Custom'){//Custom Timesheet for sum row individually
		$table .= $this->getUOMSumRow($data, $headerArr, $ts_type);
	}else{
		$table .= $this->getHoursSumRow($data);
	}
	
	if($mode != 'pending' && $mode != 'errejected' && $mode != 'erer')
	$count = count($headerArr) +2;
	else if($mode == 'pending')
	$count = count($headerArr);
	else if($mode == 'errejected' || $mode =='erer')
	$count = count($headerArr) - 1;
	//////////////////////////// Submitted date ////////////////
	$table .= '<tr class=hthbgcolor><td colspan='.$count.' class="nowrap"><font class=afontstylee>Submitted Date:&nbsp;<b>'.$data[0]['starttimedate'].'</font></td>';
	if($mode == 'errejected' || $mode == 'erer')
	{
	$table .= '<td colspan='.$count.' class="nowrap"><font class=afontstylee>'.'</font></td>';
	}
	$table .='</tr>';
	$table .= '</table> ';
	
	//////////////////////////// Remarks ////////////////
	if($data[0]['issues'] != '') {
	$table .= '<br /><font class=afontstylee><b>Remarks:</b>&nbsp;'.WrapText(html_tls_specialchars(stripslashes($data[0]['issues'])),60,'').'</font>';
	}
	
	////////////////notes/////////////////////////////////
	if($data[0]['notes'] !='' || $data[0]['notes'] !=NULL)
	$table .= '<br /><font class=afontstylee><b>Notes:</b>&nbsp;'.WrapText(html_tls_specialchars(stripslashes($data[0]['notes'])),60,'').'</font>';
	///////////////////////backup data////////////////////////////////
	$table.='<br/>'.$this->DisplaybackupTimesheet($sno,$ts_type);
	//$table.='<br />';
	
	///////////////////////// Timesheet Attachments ////////////////
	$table .= $this->getTimesheetAttachments($sno);
	
	echo $table;	
    }

    function getRateTypesForAllAsgn($asgnIds)
    {
	$AsgnIdStr = implode(",", $asgnIds);
	$rateTypesAsgn = array();	
		
	$select_ratemaster_asgn = "SELECT DISTINCT ratemasterid AS rateid FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t3 ON t1.asgnid = t3.sno LEFT JOIN multiplerates_master t2 ON t1.ratemasterid = t2.rateid WHERE t3.sno IN(".$AsgnIdStr.") AND ratetype='billrate' AND asgn_mode = 'hrcon' AND t2.status = 'Active'";

	$result_ratemaster_asgn=mysql_query($select_ratemaster_asgn,$this->db);
	$this->rateTypeCountSingle = mysql_num_rows($result_ratemaster_asgn);
	while($row_ratemaster_asgn=mysql_fetch_array($result_ratemaster_asgn))
	{
	    $rateTypesAsgn[] = $row_ratemaster_asgn['rateid'];
	}
	return $rateTypesAsgn;
    }
    
    function getRateTypesForAllAsgnnames($asgnIds, $inout_flag = false,$ts_type='')
    {
	$count_asgnids = count($asgnIds);
	$AsgnIdStr = "'";
	$AsgnIdStr .= implode("','", $asgnIds);
	$AsgnIdStr .= "'";
	$rateTypesAsgn = array();

	// FIXED RATES FOR TIMEINTIMEOUT
	$where_clause	= '';
	$order_by = '';

	if ($inout_flag) {

		$where_clause	= " AND t2.rateid IN ('rate1','rate2','rate3') ";
		$order_by = " ORDER BY t2.sno";
	}

	if($ts_type == 'UOM' || $ts_type == 'Custom'){
		
		$select_ratemaster_asgn = "SELECT DISTINCT ratemasterid AS rateid FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t3 ON t1.asgnid = t3.sno LEFT JOIN multiplerates_master t2 ON t1.ratemasterid = t2.rateid WHERE pusername IN(".$AsgnIdStr.") AND ratetype='billrate' AND asgn_mode = 'hrcon' AND t2.status = 'Active'  AND t1.rate!='' AND  ( (t1.rate>0 AND t1.ratemasterid IN ('rate2','rate3')) OR  (t1.rate>1 AND t1.ratemasterid IN ('rate1')) OR (t1.rate>0 AND t1.ratemasterid NOT IN ('rate1','rate2','rate3'))) $where_clause ";
		
	}else{
		$select_ratemaster_asgn = "SELECT DISTINCT ratemasterid AS rateid FROM multiplerates_assignment t1 INNER JOIN hrcon_jobs t3 ON t1.asgnid = t3.sno LEFT JOIN multiplerates_master t2 ON t1.ratemasterid = t2.rateid WHERE pusername IN(".$AsgnIdStr.") AND ratetype='billrate' AND asgn_mode = 'hrcon' AND t2.status = 'Active'  $where_clause $order_by";
	}
	$result_ratemaster_asgn=mysql_query($select_ratemaster_asgn,$this->db);
	$query_count = $this->rateTypeCountSingle = mysql_num_rows($result_ratemaster_asgn);
	
	
	while($row_ratemaster_asgn=mysql_fetch_array($result_ratemaster_asgn))
	{
	    $rateTypesAsgn[] = $row_ratemaster_asgn['rateid'];
	}
	return $rateTypesAsgn;
    }
    
    
	function DisplaybackupTimesheetPrint($sno)
	{
		$bakupquery="SELECT ".tzRetQueryStringDTime('approvetime','DateTimeSec','-').",auser,notes,DATE_FORMAT(approvetime,'%Y-%m-%d %H:%i:%s') FROM timesheet_hours WHERE parid='".$sno."' AND status='Backup' GROUP BY approvetime ORDER BY approvetime DESC";
		$backresult = $this->mysqlobj->query($bakupquery,$this->db);
	
		
		$display = "";
		while($backupRow=$this->mysqlobj->fetch_array($backresult))
		{
			$sql_user = "SELECT name,type from users WHERE username='".$backupRow[1]."'";
			$res_user=mysql_query($sql_user,$this->db);
			$nameAndsource=mysql_fetch_row($res_user);
			$backupNotes = html_tls_specialchars($backupRow[2],ENT_QUOTES);
			$display .=  "<tr>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle>$backupRow[0]</font></td>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle>$nameAndsource[0]</font></td>
							<td style='word-break:break-all;'><font class=afontstyle>{$backupNotes}</font></td>
							
						</tr>";
		}
		if($display !='')
		{
		
		$final_display ='
			<table width="100%" cellpadding="0" cellspacing="0" style="text-align:center;font-size:13px;">
				<tr class=hthbgcolor>
					<th class="nowrap">
					<font class=afontstyle>Date Updated</font>
					</th>
					<th class="nowrap">
					<font class=afontstyle>Updated By</font>
					</th>
					<th class="nowrap">
					<font class=afontstyle>Notes</font>
					</th>
				</tr>';
				$final_display .= $display.'</table>';
		}
		return $final_display;
	}
	
	
	function DisplaybackupTimesheet($sno,$ts_type='')
	{
		 // added global variable to pass the client id and concatinate the variable with the query.
		global $condChkCSS_History;
		
		$bakupquery="SELECT ".tzRetQueryStringDTime('approvetime','DateTimeSec','-').",auser,notes,DATE_FORMAT(approvetime,'%Y-%m-%d %H:%i:%s') FROM timesheet_hours WHERE parid='".$sno."' AND status='Backup' ".$condChkCSS_History." GROUP BY approvetime ORDER BY approvetime DESC";
		$backresult = $this->mysqlobj->query($bakupquery,$this->db);
	
		
		$display = "";
		while($backupRow=$this->mysqlobj->fetch_array($backresult))
		{
			$sql_user = "SELECT name,type from users WHERE username='".$backupRow[1]."'";
			$res_user=mysql_query($sql_user,$this->db);
			$nameAndsource=mysql_fetch_row($res_user);
			$backupNotes = html_tls_specialchars($backupRow[2],ENT_QUOTES);
			if($ts_type == 'UOM') {
				$display .=  "<tr>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle><a href='#' onclick=\"javascript:openwin_uom('$backupRow[3]', '$sno');\">$backupRow[0]</a></font></td>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle>$nameAndsource[0]</font></td>
							<td style='word-break:break-all;'><font class=afontstyle>{$backupNotes}</font></td>
							
						</tr>";
			} elseif($ts_type == 'Custom') {
				$display .=  "<tr>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle><a href='#' onclick=\"javascript:openwin_custom('$backupRow[3]', '$sno');\">$backupRow[0]</a></font></td>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle>$nameAndsource[0]</font></td>
							<td style='word-break:break-all;'><font class=afontstyle>{$backupNotes}</font></td>
							
						</tr>";
			} else {
				$display .=  "<tr>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle><a href='#' onclick=\"javascript:openwin('$backupRow[3]', '$sno');\">$backupRow[0]</a></font></td>
							<td class='nowrap' valign='top' width='14%'><font class=afontstyle>$nameAndsource[0]</font></td>
							<td style='word-break:break-all;'><font class=afontstyle>{$backupNotes}</font></td>
							
						</tr>";
			}
			
		}
		if($display !='')
		{
		
		$final_display ='
			<table width="100%" cellpadding="0" cellspacing="0" style="text-align:center;" id="history_table">
				<tr class=hthbgcolor>
					<th class="nowrap">
					<font class=afontstyle>Date Updated</font>
					</th>
					<th class="nowrap">
					<font class=afontstyle>Updated By</font>
					</th>
					<th class="nowrap">
					<font class=afontstyle>Notes</font>
					</th>
				</tr>';
				$final_display .= str_replace('\\', '', $display).'</table>';
		}
		return $final_display;
	}
	
    function getSubmitedTsDetails($empid, $asgnid, $datefrom, $dateto)
    {
	$assign_start_date = $datefrom;
	$assign_end_date = $dateto;
	
	$sql = "SELECT s.sdate, s.edate, s.task, GROUP_CONCAT(CAST(s.ratetypes AS CHAR)) AS rate, s.assid, s.classid FROM
		    (
			SELECT 	t1.sdate, t1.edate, GROUP_CONCAT(t1.task) AS task, CONCAT(t1.hourstype, '|', SUM(t1.hours), '|', t1.billable) AS ratetypes,  t1.assid, t1.classid, t1.status, t1.rowid FROM timesheet_hours t1 LEFT JOIN hrcon_jobs t2 ON t1.assid = t2.pusername WHERE t1.username = '".$empid."' AND t2.sno = '".$asgnid."' AND (t1.sdate BETWEEN '".$datefrom."' AND '".$dateto."' || t1.edate BETWEEN '".$datefrom."' AND '".$dateto."') AND t1.status IN ('ER', 'Approved', 'Build') GROUP BY t1.assid, t1.hourstype
		    ) s GROUP BY s.assid";
	$result=$this->mysqlobj->query($sql,$this->db);
	
	$row=$this->mysqlobj->fetch_array($result);		
	return $row;
    }
    
    // For getting company id of CSS User
    function getClientId($username){
	
	$sel="select staffacc_contact.username from staffacc_contactacc,staffacc_contact where staffacc_contactacc.con_id=staffacc_contact.sno and staffacc_contactacc.username = '".$username."'";
	$ressel=mysql_query($sel,$this->db);
	$rssel=mysql_fetch_row($ressel);

	$clSelsql = "SELECT sno from staffacc_cinfo WHERE type IN ('CUST', 'BOTH') AND username='".$rssel[0]."'";
	$resselSno=mysql_query($clSelsql,$this->db);
	$rsselSno=mysql_fetch_row($resselSno);
	$Cval=$rsselSno[0];
	return $Cval;
    }
    
    // get Client Id condition - CSS User
    function getClientValCond($username){
	    
	    // find client id based on assignments
	    $sel		=	"select staffacc_contact.username from staffacc_contactacc,staffacc_contact where staffacc_contactacc.con_id=staffacc_contact.sno and staffacc_contactacc.username = '$username'";
	    $ressel		=	mysql_query($sel,$this->db);
	    $rssel		=	mysql_fetch_row($ressel);

	    $clSelsql 	=	"SELECT sno from staffacc_cinfo WHERE type IN ('CUST', 'BOTH') AND username='".$rssel[0]."'";
	    $resselSno	=	mysql_query($clSelsql,$this->db);
	    $rsselSno	=	mysql_fetch_row($resselSno);
	    $Cval		=	$rsselSno[0];
	    
	    $clientcond	=	" AND th.client=$Cval ";
	    return $clientcond;
    }
    
    // get Billable condition - CSS User
    function getBillableCond($username){
	   
	    // Check user preferences For CSS User
	    $sqlSelfPref		= 	"select sno, username, joborders, candidates, assignments, placements, billingmgt, timesheet, invoices, expenses, 	joborders_owner from selfservice_pref where username='".$username."'";
	    $resSelfPref		= 	mysql_query($sqlSelfPref,$this->db);
	    $userSelfServicePref	=	mysql_fetch_row($resSelfPref);
	    
	    if(strpos($userSelfServicePref[7],"+6+"))
		    $billcond		=	" AND th.billable !='' AND th.billable !='no' ";
						    
	    return $billcond;
    }
	
    // get Client Join Table condition - CSS User
    function getClientJoinCond($username){	    
	    
	    $sqlSelfPref		= 	"select sno, username, joborders, candidates, assignments, placements, billingmgt, timesheet, invoices, expenses, joborders_owner from selfservice_pref where username='".$username."'";
	    $resSelfPref		= 	mysql_query($sqlSelfPref,$this->db);
	    $userSelfServicePref	=	mysql_fetch_row($resSelfPref);
	    
	    if(strpos($userSelfServicePref[7],"+4+") || strpos($userSelfServicePref[7],"+5+"))
	    {
		    if(strpos($userSelfServicePref[7],"+4+"))
			    $chkContact = "OR hj.contact = staffacc_contactacc.con_id";
						    
		    $clientjoin 	=	" LEFT JOIN staffacc_contactacc ON hj.manager = staffacc_contactacc.con_id ".$chkContact;
		    $clientcond		=	" AND staffacc_contactacc.username = '$username' ";
	    }
	    return $clientjoin." | ".$clientcond;
    }
    
    function getMaxRowId($parid)
    {
		$sel	 	= 	"SELECT MAX(rowid) FROM timesheet_hours WHERE parid=".$parid;
		$ressel 	=	mysql_query($sel,$this->db);
		$rssel		=	mysql_fetch_row($ressel);
		$maxRowId	=	$rssel[0];
		return $maxRowId;
    }
    
    // To handle this use case also where there are multiple values for single ratetype and rowid is also same    
    function getDetailsBySnos($snos, $ts_type = '', $mode = '')
    {
	$ratetimedata	= "";
	$rate_time_data = array();
	$col1				= ' th.hours as hours ';
	$leftcon1			= '  ';
	
	if (!empty($snos))
	{
		/*if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == 'Custom') {
			if(isset($mode) && $mode == 'backup')
			$leftcon1			= '';
		
			$ts_notes 	= ' , pn.sno as notesid, IF(th.sno = pn.tssno , "Y", "N") as notestatus, pn.tssno as tssno';
			$ts_notesleftjoin 	= ' LEFT JOIN person_ts_notes pn ON th.sno = pn.tssno '.$leftcon1.' LEFT JOIN par_timesheet p ON p.sno=th.parid and p.template="Custom" ';
			
		}*/
		$ts_notes 	= ' ,th.sno as tssno ';
		$sel_sno_query	= "SELECT
						th.parid, ".$col1.", th.hourstype as rate, th.billable as billable ".$ts_notes."
					FROM
						timesheet_hours th ".$ts_notesleftjoin."
					WHERE
						th.sno in (".$snos.")
					GROUP BY th.rowid, th.hourstype";
		$res_sno_query	= $this->mysqlobj->query($sel_sno_query,  $this->db);

		if (!$res_sno_query) {

			die('Could not connect: ' . mysql_error());
		}

		if (mysql_num_rows($res_sno_query) > 0) {

			while ($row_rate_query = $this->mysqlobj->fetch_object($res_sno_query)) {

				$rate_data	= $row_rate_query->rate."|".$row_rate_query->hours."|".$row_rate_query->billable;
				
				if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == 'Custom') {

					$rate_data	.= "|||".$row_rate_query->tssno;
				}
				
				$rate_time_data[] = $rate_data;
			}
									
			$ratetimedata = implode(",",$rate_time_data);
			
		}
	}	
	return $ratetimedata;
    }
   
	//Custom Timesheets - Added ts_type for Custom Timesheet difference
	function getCustomRangeRow($employee, $assign_id = '', $rtype = '', $task='', $assignStartEndDate, $assignStartDate, $assignEndDate, $classid, $rowid, $range='no', $timesheet_hours_sno = '', $edit_string = '', $editRowid='',$module='', $rowtotal='0.00', $cval = '',$ts_type='', $mode='', $sno ='', $ts_status='',$ts_sno='',$ts_typeid='',$rateids_arr='')
	{

		$this->mystr = array();
		$this->mystr[] = $timesheet_hours_sno;
		
		$dayWeekClass = ($range=="yes") ? "dayWeekTab2" : "dayWeekTab1";
		$rangRow = "<tr id='row_".$rowid."' class='tr_clone ".$dayWeekClass."'>";
		
		
		////////////////// Dates dropdown ///////////////////////////
		$rangRow .= "<td valign='top' width='2%' class='DeletePad'>
		<input type='hidden' id='edit_string' name='edit_string[".$rowid."]' value='".$edit_string."'>
		<input type='hidden' id='edit_snos_new' name='edit_snos_new[".$rowid."]' value='".$timesheet_hours_sno."'>
		<input type='hidden' name='tssnohdn' id='tssnohdn_".$rowid."' value='".$timesheet_hours_sno."' />";
		
		$rangRow .="<input type='checkbox' name='daily_check[".$rowid."][]' id='check_".$rowid."' value='".$timesheet_hours_sno."' class='chremove' style='margin-top:0px;display:none;' >";

		
		$rangRow .="<span name='daily_del[".$rowid."][]' id='dailydel_".$rowid."' onclick='javascript:delCloneRow(this.id)'><i class='fa fa-trash fa-2x' alt='Delete' Title='Delete'></i></span></td>";
		$rangRow .= "<td valign='top' align='left' width='10%'>";  
				
		$rangRow .= $this->buildDropDownCheck('daily_dates', $rowid, $assignStartEndDate, $assignStartDate, $script='', $key='', $val='', $range, $employee,false,'' ,$module ,$cval);
		$rangRow .= "<font title='click here to add task details' onclick='javascript:AddTaskDetails(this.id)' id='addtaskdetails_".$rowid."' class='addtaskBtn' style='padding-top: 0px; white-space:nowrap;'><i class='fa fa-tasks fa-lg'></i>Add Task Details

 </font>";
		$rangRow .= "</td>";
		
		////////////////// Assignments dropdown ///////////////////////////
		$asgnDropDown = $this->getAssignments($employee, $assign_id, $assignStartDate, $assignEndDate, $rowid,$module,'','',$cval,$ts_type);
		if(count($this->assignments) > 1)
		{
			$multicss = "background='/PSOS/images/arrow-multiple-12-red.png' style='background-repeat:no-repeat;background-position:left 12px; padding-left: 17px;word-break:break-all;overflow-wrap: break-word;'";
		}else{
			$multicss = "style='word-break:break-all;overflow-wrap: break-word;'";
		}
		$rangRow .= "<td valign='top' class='nowrap' width='32%' ".$multicss." >";
		$rangRow .= '<span id="span_'.$rowid.'">';
		$rangRow .= $asgnDropDown;
		$rangRow .= '</span>';
		$rangRow .= "<br />";
		$rangRow .= "<label id='textlabel_".$rowid."' title='click here to add task details' class='afontstylee textwrampnew' onclick='javascript:AddTaskDetails(this.id)'  style='display:inline;padding-top: 0px;float:left'>".$task."</label>";
		$rangRow .= "<input style='display: none;' class='addtaskdetails' type='text' class=afontstylee name='daily_task[0][".$rowid."]'  value='".html_tls_specialchars($task,ENT_QUOTES)."' id='np_".$rowid."' tabindex='10'>";
		$rangRow .= "</td>";
		if(strpos($assign_id, 'earn'))
		{
			$assignment_id = ($assign_id=='')?$this->assignmentIds[0]:$assign_id;
		}
		else
		{
			$assignment_id = ($assign_id=='')?$this->assignmentIds[0]:$this->getAssignId($assign_id);
		}	
			
		//To get person drop down list when THERAPY_SOURCE is ENABLED
		
		////////////////// Person dropdown ///////////////////////////
		if(THERAPY_SOURCE_ENABLED == 'Y' && $ts_type == 'Custom'){
			$onchanheType ='';
			$personslistStr = $this->getPersonDropDownList($assignment_id,$timesheet_hours_sno,$mode);
			$personslists = explode("^^", $personslistStr);
			$personslist = $personslists[0];
			$styleVisaable ="";
			if ($personslists[1] == "N") {
				$styleVisaable = "visibility: hidden;";
			}
			if ($module == "MyProfile") {
				$styleVisaable = "visibility: hidden;";
			}
			if($mode == 'edit')
				$onchanheType = "";
			$rangRow .= "<td valign='top' width='8%'><select name='daily_person[".$rowid."]' id='daily_person_".$rowid."' class='daily_person  select2-select-person akkenPersonSelectWid' ".$onchanheType." >".$personslist;
			
			$editlist = '</select><div class="marginNote"><span style="'.$styleVisaable.'" href="#" onclick="displayCustomNames(\'daily_assignemnt_'.$rowid.'\',document.getElementById(\'daily_person_'.$rowid.'\').value);" id="person_list_'.$rowid.'" class="notesclass"><i class="fa fa-list-alt fa-lg" aria-hidden="true"></i>Edit List</span>&nbsp;';
			
			
			
			$person_notes_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachedNoteDetails']['parid_'.$sno]['tsrowid_'.$rowid];
			$person_notes = '';
			
			if (count($person_notes_arry)>0) {
				if (!empty($person_notes_arry['tssno_0'])) {
					$person_notes = $person_notes_arry['tssno_0'].'|Y';
				}
			}

			$person_attach_arry = $this->eachrowidTotalPersonAttachmentValArr['PersonAttachmentsCountDetails']['parid_'.$sno]['tsrowid_'.$rowid];
			$person_attach = '';
			if (count($person_attach_arry)>0) {
				if (!empty($person_attach_arry['tssno_0'])) {
					$person_attach = $person_attach_arry['tssno_0'];							
				}
			}
			
			if(!empty($person_notes) || !empty($person_attach)){	
				$person_notes = $this->getpersonnotes($sno, $rowid, $mode, $ts_status);			
				$rangRow .= $html.'&nbsp;'.$editlist.'<span id="person_note_'.$rowid.'" onclick="addPersonNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'person_note_'.$rowid.'\');" class="notesclass"><i class="fa fa-edit fa-lg"></i>Edit Notes </span></div>';
			}else{
				$rangRow .= $html.'&nbsp;'.$editlist.'<span id="person_note_'.$rowid.'" onclick="addPersonNotes(document.getElementById(\'daily_assignemnt_'.$rowid.'\').value,document.getElementById(\'daily_person_'.$rowid.'\').value,\'person_note_'.$rowid.'\');" class="notesclass"><i class="fa fa-file-o fa-lg"></i>Add Notes</span></div>';
			}
			$rangRow .= '</td>';
			
			//$CustomTypelist = $this->getCustomTypeList($timesheet_hours_sno);
			$CustomTypelist = $this->getCustomTypeDropDownList($assignment_id,$timesheet_hours_sno,$mode);
			$TypeDisplaystyle ='';
			if ($module == 'MyProfile' || $module == 'Client') {
				$TypeDisplaystyle ='display:none;';
			}
			$editTypeList = '<div class="marginNote"><span style="'.$TypeDisplaystyle.'" href="#" onclick="displayCustomTypes(\'daily_assignemnt_'.$rowid.'\',document.getElementById(\'daily_cust_type_'.$rowid.'\').value);" id="cust_type_list_'.$rowid.'" class="notesclass"><i class="fa fa-list-alt fa-lg" aria-hidden="true"></i>Edit List</span></div>';
			if($mode == 'edit')
				$onchanheType = "";

			$rangRow .= "<td valign='top' width='8%'><select name='daily_cust_type[".$rowid."]' id='daily_cust_type_".$rowid."' class='daily_classes select2-select-type akkencustSelectTypeWid' ".$onchanheType."><option value='0'>---Select Type---</option>".$CustomTypelist."</select>".$editTypeList."</td>";
		}
		
		$rangRow .= "<div id='raterow_".$rowid."'>".$this->getRateTypesWithPayNBillSingle_CUSTOM($assignment_id, $rtype, $rowid, $sno, $mode, '', 'single', $this->getRateTypesForAllAsgnnames($this->listOfAssignments, false, true), $ts_type, $notes, $timesheet_hours_sno,$module,$rateids_arr)."<div>";
		
		
		///////////////////////// Total hours /////////////////////////
		//$rangRow .= "<td valign='top' class='afontstylee' width='3%'><input type='hidden' name='daytotalhrs_".$rowid."' id='daytotalhrs_".$rowid."' value='0.00' ><div id='daytotalhrsDiv_".$rowid."' style='display:none;'>0.00</div></td>";
		$tuom = '';
		/*if($ts_type == 'UOM'){//For UOM Timesheet input hidden fields for totals
			$tuom = "<input type='hidden' name='totaluomdays_".$rowid."' id='totaluomdays_".$rowid."' value='".$rowtotal."' ><input type='hidden' name='totaluommiles_".$rowid."' id='totaluommiles_".$rowid."' value='".$rowtotal."' ><input type='hidden' name='totaluomunits_".$rowid."' id='totaluomunits_".$rowid."' value='".$rowtotal."' >
					<input type='text' name='daystotalDiv_".$rowid."' id='daystotalDiv_".$rowid."' value='".$rowtotal."' style='display:none;'><input type='text' name='milestotalDiv_".$rowid."' id='milestotalDiv_".$rowid."' value='".$rowtotal."' style='display:none;'><input type='text' name='unitstotalDiv_".$rowid."' id='unitstotalDiv_".$rowid."' value='".$rowtotal."' style='display:none;'>";
		}*/
		
		
		$rangRow .= '</tr>';
		
		//Adding script to append new select for rendered html selects.
		$rangRow .= "<script>var customSelectElement = $('#MainTable #row_".$rowid."  select.select2-select');bindSelect2(customSelectElement);</script>";
		$rangRow .= "<script>var customSelectElementPerson = $('#MainTable #row_".$rowid." select.select2-select-person');bindSelect2(customSelectElementPerson);</script>";
		$rangRow .= "<script>var customSelectElementType = $('#MainTable #row_".$rowid." select.select2-select-type');bindSelect2(customSelectElementType);</script>";

		return $rangRow;
	}
	///////////////////////////////////////////
	function getPersonAttachedNoteDetails($parid,$mode){
    		
    	$ts_pernotes_query = "select sno,notes,tsrowid,tssno from person_ts_notes where parid = ".$parid." and notes !='' ";
		
		if(isset($mode) && $mode == 'approved'){
			$ts_pernotes_query	.= " and status = 'Approved' ";
		}
		elseif(isset($mode) && $mode == 'backup'){
			$ts_pernotes_query	.= " and status = 'Backup' ";
		}else{
			$ts_pernotes_query	.= " and status != 'Backup' ";
		}

    	$ts_pnotes	= 	$this->mysqlobj->query($ts_pernotes_query,  $this->db);
	    $tssnoDetails = array();
	    $tssnoDetails['parid_'.$parid] = array();
		/*if (mysql_num_rows($ts_pnotes)>0) {
			$ts_pernotes_res=$this->mysqlobj->fetch_row($ts_pnotes);
			$status = 'Y|'.$ts_pernotes_res[1];
		}else{
			$status = 'N|0';
		}*/
		while($myrow=$this->mysqlobj->fetch_row($ts_pnotes))
		{
			$tsrowid = $myrow[2];
			$tssno = $myrow[3];
			$tsnote = $myrow[1];

			if(!array_key_exists('tsrowid_'.$tsrowid, $tssnoDetails['parid_'.$parid])){
				$tssnoDetails['parid_'.$parid]['tsrowid_'.$tsrowid]=array('tssno_'.$tssno=>$tsnote);
			}else{
				
				$tssnoDetails['parid_'.$parid]['tsrowid_'.$tsrowid]['tssno_'.$tssno] = $tsnote;
			}			
		}
		return $tssnoDetails;
    }

    function getPersonAttachmentsCountDetails($parid,$mode){

    	$ts_pernotes_query   = "SELECT COUNT(sno) AS snoCount,tsrowid,tssno FROM person_ts_notes 
								WHERE parid = '".$parid."' AND filename!='' AND filesize!='' AND filetype!='' ";
		
		if(isset($mode) && $mode == 'approved'){
			$ts_pernotes_query	.= " AND status = 'Approved' ";
		}
		elseif(isset($mode) && $mode == 'backup'){
			$ts_pernotes_query	.= " AND status = 'Backup' ";
		}else{
			$ts_pernotes_query	.= " AND status != 'Backup' ";
		}
		$ts_pernotes_query	.= " GROUP BY tsrowid,tssno ";

	    $ts_pnotes	= 	$this->mysqlobj->query($ts_pernotes_query,  $this->db);
	    $tssnoDetails = array();
	    $tssnoDetails['parid_'.$parid] = array();
		/*if (mysql_num_rows($ts_pnotes)>0) {
			$ts_pernotes_res=$this->mysqlobj->fetch_row($ts_pnotes);
			$status = 'Y|'.$ts_pernotes_res[1];
		}else{
			$status = 'N|0';
		}*/
		while($myrow=$this->mysqlobj->fetch_row($ts_pnotes))
		{
			$tsrowid = $myrow[1];
			$tssno = $myrow[2];
			$snoCount = $myrow[0];

			if(!array_key_exists('tsrowid_'.$tsrowid, $tssnoDetails['parid_'.$parid])){
				$tssnoDetails['parid_'.$parid]['tsrowid_'.$tsrowid]=array('tssno_'.$tssno=>$snoCount);
			}else{
				
				$tssnoDetails['parid_'.$parid]['tsrowid_'.$tsrowid]['tssno_'.$tssno] = $snoCount;
			}			
		}
		return $tssnoDetails;
    }
	///////////////////////////////////////////
	//Function to get Perston Details
	
	function getPersonDropDownList($hrconsno='',$timesheetsno='',$mode='')
	{  
		global $username;
		$editlistdisplay = 'Y';
		$personslistidsArray= array();
		$select_personids ="SELECT sno,client,endclient FROM hrcon_jobs WHERE sno='".$hrconsno."'";
		$result_person_ids = mysql_query($select_personids);
		if(mysql_num_rows($result_person_ids)!=0){
			$hrconSno = mysql_fetch_row($result_person_ids);
			$compId = $hrconSno[1];
			$locId = $hrconSno[2];

			$selectPersonList = "SELECT GROUP_CONCAT(person_id) AS personids FROM persons_assignment WHERE asgnid ='".$hrconSno[0]."' AND asgn_mode='hrcon' ";
			$result_person_list = mysql_query($selectPersonList);
			$rows = mysql_fetch_assoc($result_person_list);
			if(!empty($rows['personids'])){
				$personslistids= $rows['personids'];
				$editlistdisplay = 'N';
				$where= "WHERE p.status !='Backup' AND p.sno IN(".$personslistids.")";
			}else{
				$where= "WHERE p.status != 'Backup'
						AND p.hjobs_compid = ".$compId."
						AND p.location_id = ".$locId."
						AND p.person_type='T'";
			}
		}else{
			$where= "WHERE p.status != 'Backup'
					AND p.hjobs_compid = ".$compId."
					AND p.location_id = ".$locId."
					AND p.person_type='T'";
		}
		$query= "SELECT p.sno, p.fname, p.lname
				FROM person_info p
				LEFT JOIN hrcon_jobs hj ON hj.client = p.hjobs_compid			
				AND p.location_id = hj.endclient 
				".$where."
				group by p.sno order by fname asc";

		$result = $this->mysqlobj->query($query,$this->db);
		$arrcount 		= mysql_num_rows($result);
		$perdata = array();
		if ($arrcount == 0 ) {
			$htmlRow = '<option value="0">--Select--</option>';
		}
		
		$selectedid = 0;
		if(isset($timesheetsno) && !empty($timesheetsno) && $mode == 'edit'){
			$assignperson = $this->getAssignPerson($timesheetsno);
		}
		while($myrow=$this->mysqlobj->fetch_row($result))
		{

			if( !empty($mode) && $mode=='edit' && $myrow[0] == $assignperson ){
				$select = ' selected = "selected"';
			}elseif( !empty($mode) && $mode=='selectlist' && $myrow[0] == $timesheetsno ){
				$select = ' selected = "selected"';
				$selectedid = $timesheetsno;
			}
			else{
				$select ='';
			}
			$this->new_first_user = $myrow[1]." ".$myrow[2];					
			$htmlRow .= '<option value="'.$myrow[0].'"'.$select.'>'.$this->new_first_user.'</option>';
		}
			
		return $htmlRow.'^^'.$editlistdisplay;
	}

	function getPersonWithAjax($compId = '', $locId = '',$mode = '', $timesheetsno,$hrassignid='',$hrsno='')
	{  
		global $username;
		$editlistdisplay = 'Y';
		$personslistidsArray= array();
		$select_personids ="SELECT sno FROM hrcon_jobs WHERE pusername='".$hrassignid."'";
		$result_person_ids = mysql_query($select_personids);
		if(mysql_num_rows($result_person_ids)!=0){
			$hrconSno = mysql_fetch_row($result_person_ids);
			$selectPersonList = "SELECT person_id FROM persons_assignment WHERE asgnid ='".$hrconSno[0]."' AND asgn_mode='hrcon' ";
			$result_person_list = mysql_query($selectPersonList);
			while($rows = mysql_fetch_array($result_person_list)){
				$personslistidsArray[]=$rows[0];
			}
			if(count($personslistidsArray)>0){
				$personslistids = implode(',',$personslistidsArray);
				$editlistdisplay = 'N';
				$where= "WHERE p.status !='Backup' AND p.sno IN(".$personslistids.")";
			}else{
				$where= "WHERE p.status != 'Backup'
						AND p.hjobs_compid = ".$compId."
						AND p.location_id = ".$locId."
						AND p.person_type='T'";
			}
		}else{
			$where= "WHERE p.status != 'Backup'
					AND p.hjobs_compid = ".$compId."
					AND p.location_id = ".$locId."
					AND p.person_type='T'";
		}
		$query= "SELECT p.sno, p.fname, p.lname
				FROM person_info p
				LEFT JOIN hrcon_jobs hj ON hj.client = p.hjobs_compid			
				AND p.location_id = hj.endclient  
				".$where."
				group by p.sno order by fname asc";

		$result = $this->mysqlobj->query($query,$this->db);
		$arrcount 		= mysql_num_rows($result);
		$perdata = array();
		if ($arrcount == 0 ) {
			$htmlRow = '<option value="0">--Select--</option>';
		}
		
		$selectedid = 0;
		if(isset($timesheetsno) && !empty($timesheetsno) && $mode == 'edit'){
			$assignperson = $this->getAssignPerson($timesheetsno);
		}
		while($myrow=$this->mysqlobj->fetch_row($result))
		{

			if( !empty($mode) && $mode=='edit' && $myrow[0] == $assignperson ){
				$select = ' selected = "selected"';
			}elseif( !empty($mode) && $mode=='selectlist' && $myrow[0] == $timesheetsno ){
				$select = ' selected = "selected"';
				$selectedid = $timesheetsno;
			}
			else{
				$select ='';
			}
			$this->new_first_user = $myrow[1]." ".$myrow[2];					
			$htmlRow .= '<option value="'.$myrow[0].'"'.$select.'>'.$this->new_first_user.'</option>';
		}
			
		echo $htmlRow.'^^'.$editlistdisplay.'^^'.$selectedid;
	}
	//Function to get selected person
	function getAssignPerson($timesheetsno)
	{
		$ts_ass_person_query	= 	"SELECT p.sno
					FROM person_info p 
					LEFT JOIN timesheet_hours th ON th.person_id=p.sno					 
					WHERE p.status != 'Backup'";
		if(isset($timesheetsno) && !empty($timesheetsno)){
			$ts_ass_person_query.= 	" AND th.sno IN  ('".$timesheetsno."') ";
		}
		$ts_ass_person=$this->mysqlobj->query($ts_ass_person_query,$this->db);
		$ts_ass_person_res=$this->mysqlobj->fetch_row($ts_ass_person);
		$persno = $ts_ass_person_res[0];
		return $persno;
	}
	//Function to get headers
	function getCustomHeaders(){
		$headerqry		= "SELECT person FROM manage_person_details ";
		$headerqry		= $this->mysqlobj->query($headerqry,$this->db);
		$headerresults	= $this->mysqlobj->fetch_row($headerqry);
		return $headerresults[0];
	}
	//Function to get the " Type " for Custom Timesheet
	function getCustomTypeList($thsno=''){
		$selected='';
		$selectedTypeSno = $this->getCustomTypeListName($thsno);
		$headerqry	= "SELECT `sno`,`type_name`,`code` FROM custom_type WHERE  `status` NOT IN('Backup','Inactive')";
		$resultheaderqry	= $this->mysqlobj->query($headerqry,$this->db);
		while($myrow=$this->mysqlobj->fetch_array($resultheaderqry))
		{	
			$selected ='';
			$typeSno = 0;
			if($myrow['sno'] == $selectedTypeSno)
			{
				$selected = "selected='selected'";
				$typeSno = $selectedTypeSno;
			}
			$typecode='';
			if ($myrow['code'] !="") {
				$typecode=" (".$myrow['code'].")";
			}
			$options.= "<option value='".$myrow['sno']."' ".$selected.">".$myrow['type_name'].$typecode."</option>";
		}
		return $options;
	}
	//Function to get Custom Type from custom_type table
	function getCustomTypeListName($thsno=0){
		if(!empty($thsno)){
			$selectheaderqry = "SELECT DISTINCT(ct.sno) FROM custom_type ct,timesheet_hours th WHERE th.cust_type = ct.sno AND th.sno IN (".$thsno.")";
			$headerqry		= $this->mysqlobj->query($selectheaderqry,$this->db);
			$headerresults=$this->mysqlobj->fetch_array($headerqry);
			$result = $headerresults['sno'];
		}else{
			$result = 0;
		}
		return $result;
	}
	function getCustomTypeDropDownList($hrconsno='',$timesheetsno='',$mode='')
	{  
		global $username;
		$compId = 0;
		$locId = 0;
		$select_typeids ="SELECT sno,client,endclient FROM hrcon_jobs WHERE sno='".$hrconsno."'";
		$result_type_ids = mysql_query($select_typeids);
		if(mysql_num_rows($result_type_ids)!=0){
			$hrconSno = mysql_fetch_row($result_type_ids);
			$compId = $hrconSno[1];
			$locId = $hrconSno[2];
		}
		$query= "SELECT
				ct.sno,ct.type_name,ct.code,ct.type_display,'',''
				FROM custom_type ct
				WHERE 1=1 AND ct.status IN ('Active')   
				AND ct.type_display ='Y'  

				UNION 

				SELECT
				ct.sno,ct.type_name,ct.code,ct.type_display,cta.hjobs_compid,cta.location_id
				FROM custom_type ct 
				JOIN custom_type_assoc cta ON (cta.type_id = ct.sno AND cta.hjobs_compid='".$compId."' AND cta.location_id='".$locId."')
				WHERE 1=1 AND ct.status IN ('Active')   
				AND ct.type_display ='N'   
				GROUP BY ct.sno order by type_name asc";

		$result = $this->mysqlobj->query($query,$this->db);
		$arrcount 		= mysql_num_rows($result);
		//$options = '<option value="0">--Select Type--</option>';
		
		$selectedTypeSno = 0;
		if(isset($timesheetsno) && !empty($timesheetsno) && $mode == 'edit'){
			$selectedTypeSno = $this->getCustomTypeListName($timesheetsno);
		}
		while($myrow=$this->mysqlobj->fetch_array($result))
		{	
			$selected ='';
			$code = '';
			if ($myrow['code'] !="") {
				$code = ' ('.$myrow['code'].')';
			}
			if($myrow['sno'] == $selectedTypeSno)
			{
				$selected = "selected='selected'";
			}
			$options.= "<option value='".$myrow['sno']."' ".$selected.">".$myrow['type_name'].$code."</option>";
		}
		
		return $options;
	}
	//Function used to return options for the Custom Type
	function getCustomTypeWithAjax($compId = '', $locId = '', $selectedTypeSno,$tssno =''){

		$options = '<option value="0">--Select Type--</option>';
		$headerqry	= "SELECT
						ct.sno,ct.type_name,ct.code,ct.type_display,'',''
						FROM custom_type ct
						WHERE 1=1 AND ct.status IN ('Active')   
						AND ct.type_display ='Y'  

						UNION 

						SELECT
						ct.sno,ct.type_name,ct.code,ct.type_display,cta.hjobs_compid,cta.location_id
						FROM custom_type ct 
						JOIN custom_type_assoc cta ON (cta.type_id = ct.sno AND cta.hjobs_compid='".$compId."' AND cta.location_id='".$locId."')
						WHERE 1=1 AND ct.status IN ('Active')   
						AND ct.type_display ='N'   
						GROUP BY ct.sno order by type_name asc";
		$resultheaderqry	= $this->mysqlobj->query($headerqry,$this->db);
		if ($selectedTypeSno ==0) {
			$selectedTypeSno = $this->getCustomTypeListName($tssno);
		}
		while($myrow=$this->mysqlobj->fetch_array($resultheaderqry))
		{	
			$selected ='';
			$code = '';
			if ($myrow['code'] !="") {
				$code = ' ('.$myrow['code'].')';
			}
			if($myrow['sno'] == $selectedTypeSno)
			{
				$selected = "selected='selected'";
			}
			$options.= "<option value='".$myrow['sno']."' ".$selected.">".$myrow['type_name'].$code."</option>";
		}
		if ($selectedTypeSno==0 || $selectedTypeSno =="") {
			$selectedTypeSno = 0;
		}
		return $options.'^^'.$selectedTypeSno;
	}

	//Function to get hour notes details
	function gettsnotesDetails($tssno, $col, $row, $ts_status=''){
		$ts_notes_query	= 	"SELECT p.sno as notesid, p.notes as notes, p.filename as notesfilename, 
							p.filesize as notesfilesize, p.filetype as notesfiletype,
							th.person_id as person_id, th.parid
							FROM person_ts_notes p
							LEFT JOIN timesheet_hours th ON th.sno = p.tssno
							WHERE p.tssno IN (".$tssno.") AND p.tsrowid = ".$row." AND p.status != 'Backup'"; 
		$ts_notes_res	= $this->mysqlobj->query($ts_notes_query,  $this->db);
		$myrow=$this->mysqlobj->fetch_row($ts_notes_res);
		$customNotes = array("assignmentid"=>'', 
			"raterowid"=>'daily_rate_'.$col.'_'.$row.'',
			"raterowdate"=>'',
			"personid"=>$myrow->person_id,
			"notes"=>$myrow[1],
			"attachments" => array(),
			"savemode"=>'DB',
			"parentid"=>$myrow[0]
		);
		$ts_notes_res1	= $this->mysqlobj->query($ts_notes_query,  $this->db);
		while ($ts_rateAttach_res=$this->mysqlobj->fetch_array($ts_notes_res1)) {
			$filesval = array(
				"filename"=>$ts_rateAttach_res[2],
				"filesize"=>$ts_rateAttach_res[3],
				"filetype"=> $ts_rateAttach_res[4],
				"temppath"=>'',
				"savemode"=>'DB',
				"parentid"=>$ts_rateAttach_res[0]
			);
			$_SESSION['TimeSheetNotesTotalSize'] = $_SESSION['TimeSheetNotesTotalSize']+round($ts_rateAttach_res[3]);
			array_push($customNotes['attachments'], $filesval);
		}
		$_SESSION['AddCustomTimeSheetNotes']['row_'.$col.'_'.$row.''] = $customNotes;		
	}
	
	//Function to get hour notes
	function gettsnotes($tssno, $mode = ''){
		$ts_notes_query	= 	"SELECT notes FROM person_ts_notes WHERE tssno = ".$tssno." ";
		if(isset($mode) && $mode == 'approved'){
			$ts_notes_query	.= " and status = 'Approved' ";
		}
		elseif(isset($mode) && $mode == 'backup'){
			$ts_notes_query	.= " and status = 'Backup' ";
		}else{
			$ts_notes_query	.= " and status != 'Backup' ";
		}
		$ts_notes_res	= 	$this->mysqlobj->query($ts_notes_query,  $this->db);
		$ts_notes	=	$this->mysqlobj->fetch_row($ts_notes_res);
		return $ts_notes[0];			
	}

	//Function to get hour notes
	function getTsRateAttachmentsCount($tssno, $mode = ''){
		$ts_notes_query	= 	"SELECT COUNT(sno) AS snoCount,parid,tsrowid FROM person_ts_notes WHERE tssno = ".$tssno." AND filename!='' AND filesize!='' AND filetype!='' ";
		if(isset($mode) && $mode == 'approved'){
			$ts_notes_query	.= " and status = 'Approved' ";
		}
		elseif(isset($mode) && $mode == 'backup'){
			$ts_notes_query	.= " and status = 'Backup' ";
		}else{
			$ts_notes_query	.= " and status != 'Backup' ";
		}
		$ts_notes_res	= 	$this->mysqlobj->query($ts_notes_query,  $this->db);
		if (mysql_num_rows($ts_notes_res)>0) {
			$ts_pernotes_res=$this->mysqlobj->fetch_row($ts_notes_res);
			$status = 'Y|'.$ts_pernotes_res[1].'|'.$ts_pernotes_res[2];
		}else{
			$status = 'N|0|0';
		}
			
	    return $status;			
	}
        
	//Function to get person notes
	function getpersonnotes($parid, $rowid, $mode = ''){
	   $ts_pernotes_query   = " select sno, notes, filename, filesize, filetype, person_id from person_ts_notes where parid = ".$parid." and tsrowid = ".$rowid." and tshourstype = 'person' ";
		
		if(isset($mode) && $mode == 'approved'){
			$ts_pernotes_query	.= " and status = 'Approved' ";
		}
		elseif(isset($mode) && $mode == 'backup'){
			$ts_pernotes_query	.= " and status = 'Backup' ";
		}else{
			$ts_pernotes_query	.= " and status != 'Backup' ";
		}
		//$ts_pernotes_query .= " GROUP BY tssno,parid,notes,filename,filesize,filecontent,filetype,tsrowid,person_id,tshourstype,`status`";
	    $ts_pnotes	= 	$this->mysqlobj->query($ts_pernotes_query,  $this->db);
		$ts_pernotes_res=$this->mysqlobj->fetch_row($ts_pnotes);
		
		if($mode == 'edit' && $ts_pernotes_res > 0){
				$ts_person_ses_Notes = array("assignmentid"=>'', 
				"raterowid"=>$rowid,
				"raterowdate"=>'',
				"personid"=>$personid,
				"notes"=>$ts_pernotes_res[1],
				"attachments" => array(),
				"savemode"=>'DB',
				"parentid"=>$ts_pernotes_res[0]
				);
			 $ts_pnotes1	= 	$this->mysqlobj->query($ts_pernotes_query, $this->db);
			while ($ts_persnAttach_res=$this->mysqlobj->fetch_array($ts_pnotes1)) {
				$filesval = array(
					"filename"=>$ts_persnAttach_res[2],
					"filesize"=>$ts_persnAttach_res[3],
					"filetype"=>$ts_persnAttach_res[4],
					"temppath"=>'',
					"savemode"=>'DB',
					"parentid"=>$ts_persnAttach_res[0]
				);
				$_SESSION['TimeSheetNotesTotalSize'] = $_SESSION['TimeSheetNotesTotalSize']+round($ts_persnAttach_res[3]);
				array_push($ts_person_ses_Notes['attachments'], $filesval);
			}			
			
			$_SESSION['AddPersonTimeSheetNotes']['person_note_'.$rowid.''] = $ts_person_ses_Notes;			
		}
		
		$status = 'N';
		if($ts_pernotes_res > 0){
			$status = 'Y';
		}		
	    return $ts_pernotes_res[1].'|'.$status;
	}

	//Function to get person Attachments
	function getpersonAttachmentsCount($parid, $rowid, $mode = ''){
	   $ts_pernotes_query   = " SELECT COUNT(sno) AS snoCount,tssno FROM person_ts_notes WHERE parid = ".$parid." AND tsrowid = ".$rowid." AND tshourstype = 'person' AND filename!='' AND filesize!='' AND filetype!='' ";
		
		if(isset($mode) && $mode == 'approved'){
			$ts_pernotes_query	.= " AND status = 'Approved' ";
		}
		elseif(isset($mode) && $mode == 'backup'){
			$ts_pernotes_query	.= " AND status = 'Backup' ";
		}else{
			$ts_pernotes_query	.= " AND status != 'Backup' ";
		}
	    $ts_pnotes	= 	$this->mysqlobj->query($ts_pernotes_query,  $this->db);
		if (mysql_num_rows($ts_pnotes)>0) {
			$ts_pernotes_res=$this->mysqlobj->fetch_row($ts_pnotes);
			$status = 'Y|'.$ts_pernotes_res[1];
		}else{
			$status = 'N|0';
		}
			
	    return $status;
	}

    function displayTimesheetDetailsEmail_UOM($sno, $mode,$condinvoice ='',$conjoin='', $module='')
    {
	$table = '<div id="grid_form" class="grid_forms" style="white-space:nowrap">';
	$table .= '<table cellspacing="1" cellpadding="5" width="100%"  border=0 style="text-align:left;"> ';
		
	// get the timesheet details
	$data = $this->getTimesheetDetails($sno, $mode,$condinvoice,$conjoin,$module,'UOM');

	foreach($data as $val)
	{

		$usernamedb = $val['username'];
		$servicedateto = $val['penddate'];
		$servicedate = $val['pstartdate'];
	    
	}
		$this->getAssignments($usernamedb, '', $servicedate, $servicedateto, '0');
		$ratesArr =     $this->getRateTypesForAllAsgnnames($this->assignments);
	
	$headerArr = array('Date', 'Assignments');
	
	if(MANAGE_CLASSES == 'Y')
	{
	    array_push($headerArr, 'Class');
	}
	$headerCount = count($headerArr);
	$ratetype = $this->getRateTypes();
	$rateCount = count($ratesArr);
	foreach($ratetype as $val)
	{
	    if(in_array($val['rateid'], $ratesArr))
	    {
		array_push($headerArr, $val['name']);
	    }
	}
	/////////////////////////// Main Headers ///////////////////////
	
	$table .= $this->buildMainHeaders($headerArr,$mode);
	//////////////////////// Sub Headers (Hour & Billable) ////////
	$table .= $this->buildSubHeaders($headerArr, $headerCount,$mode,'UOM');
	
	
	foreach($data as $key=>$val)
	{
	    
		$table .= $this->buildRow($val, $key, $rateCount,$mode, $module, $data,$print=True);
	}
	////////////// Total hours //////////
	
	$table .= $this->getUOMSumRowEmail_UOM($data,$headerArr);
	
	if($mode != 'pending' && $mode != 'errejected' && $mode != 'erer')
	$count = count($headerArr) +2;
	else if($mode == 'pending')
	$count = count($headerArr);
	else if($mode == 'errejected' || $mode =='erer')
	$count = count($headerArr) - 1;
	//////////////////////////// Submitted date ////////////////
	$table .= '<tr class=hthbgcolor><td colspan='.$count.' class="nowrap"><font class=afontstylee>Submitted Date:&nbsp;<b>'.$data[0]['starttimedate'].'</font></td>';
	if($mode == 'errejected' || $mode == 'erer')
	{
	$table .= '<td colspan='.$count.' class="nowrap"><font class=afontstylee>'.'</font></td>';
	}
	$table .='</tr>';
	$table .= '</table> ';
	$table .= '</div>';
		
	return $table;	
    }
    function getUOMSumRowEmail_UOM($data,$headerArr='')
    {
		$rate_totalsort_arr = array();
		$rate_totalunit_arr = array();
		$rate_inc ='';
		
		$sum_hours ='';
		$headerArr_rates=array();
		$headerArr_rates = $headerArr;
		array_shift($headerArr_rates);
		$header_rates= implode(',',$headerArr_rates);
		$header_rates=str_replace(",","','",$header_rates);
		
		$rate_que = "select sno,rateid,name from multiplerates_master where name IN ('".$header_rates."')";
		$rate_que_sel=$this->mysqlobj->query($rate_que,$this->db);
	
		while($rate_que_myrow=$this->mysqlobj->fetch_array($rate_que_sel))
		{
			$rate_que_myrow['rateid'] =substr($rate_que_myrow['rateid'],4);
			$ratetypes_ids[$rate_que_myrow['rateid']] = $rate_que_myrow['name'];			
		}
		
		$count = count($data);
		$sum = 0;
		$uomDay_sum = 0;
		$uomMile_sum = 0;
		$uomUnit_sum = 0;
		$hourSum = 0;
		$hr_inc = 0;$day_inc = 0;$mile_inc = 0;$unit_inc = 0;
		for($i = 0; $i < $count; $i++)
		{
			$rateArr=array();
			$rate_unit_arr = array();
			
			$ratesSel = explode(',',$data[$i]['time_data']);
			
			
			//Multiple rates array START
			if($data[$i]['mulrates'] !=''){
				$multipleRates = explode('&&',$data[$i]['mulrates']);
				for($p=0;$p<count($multipleRates);$p++){
					$getType = explode('^^',$multipleRates[$p]);
					$rate_unit_arr[$getType[0]]['rate_type'] = $getType[1];
					
				}
			}
			for($r=0;$r<count($ratesSel);$r++){
				$rateVal = explode('|',$ratesSel[$r]);
				$rateArr[$rateVal[0]] = $rateVal[1];
				$rate_unit_arr[$rateVal[0]]['units_cover'] = $rateVal[1];
			}
			
			if($rate_unit_arr){
				foreach($rate_unit_arr as $rate_key=>$rate_val){
					
					if($rate_val['rate_type'] == 'UOM_MILE'){
						$rate_totalunit_arr[$rate_key]['total_miles'] += $rate_val['units_cover'];
						$mile_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_DAY'){
						$rate_totalunit_arr[$rate_key]['total_days'] += $rate_val['units_cover'];
						$day_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_UNIT'){
						$rate_totalunit_arr[$rate_key]['total_units'] += $rate_val['units_cover'];
						$unit_inc++;
					}
					else {
						$rate_totalunit_arr[$rate_key]['total_hours'] += $rate_val['units_cover'];
						$hr_inc++;
					}
					$rate_inc =substr($rate_key,4);
					$rate_totalsort_arr[$rate_inc] = $rate_totalunit_arr[$rate_key];
				}
				
			}
			
			$sum = $sum + $data[$i]['sumhours'];
		}
		foreach($ratetypes_ids as $ratetypes_ids_key=>$ratetypes_ids_val){
			if(!array_key_exists($ratetypes_ids_key,$rate_totalsort_arr)){
				$rate_totalsort_arr[$ratetypes_ids_key]=array();
			}
		}
		ksort($rate_totalsort_arr);
			
		///////////////////////////// Total hours ////////////////////
		$sum_hours .= '<tr class=hthbgcolor><td colspan="1" style="color:#000000;">';
		$sum_hours .= '<font class=afontstyle>&nbsp;</font></td>';
		if(in_array('Class',$headerArr)){
			$sum_hours .= '<td></td>';
		}
		$sum_hours .= '<td align=right style="color:#000000;">';
		if($hr_inc != 0){
			$sum_hours .= '<div><font class=afontstylee>Total Hours: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($day_inc != 0){
			$sum_hours .= '<div><font class=afontstylee >Total Days: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($mile_inc != 0){
			$sum_hours .= '<div><font class=afontstylee >Total Miles: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($unit_inc != 0){
			$sum_hours .= '<div><font class=afontstylee >Total Units: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		$sum_hours .= '</td>';
		foreach($rate_totalsort_arr as $total_key => $total_val){
			$sum_hours .= '<td align=right style="color:#000000;">';
			
				if($total_val['total_hours'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_hours'],2,'.','').'</font></div>';
				}
				else if($hr_inc != 0 ){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
				if($total_val['total_days'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_days'],2,'.','').'</font></div>';
				}
				else if($day_inc != 0 ){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
				if($total_val['total_miles'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_miles'],2,'.','').'</font></div>';
				}
				else if($mile_inc != 0 ){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
				if($total_val['total_units'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_units'],2,'.','').'</font></div>';
				}
				else if($unit_inc != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
			
			$sum_hours .= '</td>';
		}
		$sum_hours .= '</tr>';
		$sum_hours .= '<tr class=hthbgcolor><td colspan="1"></td>';
		if(in_array('Class',$headerArr)){
			$sum_hours .= '<td></td>';
			$tu =3;
		}else{
			$tu =3;
		}
		$sum_hours .= '<td style="color:#000000;" align="right"><div><font class=afontstylee>Grand Total: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div></td>';
		for($tu;$tu<count($headerArr)-1;$tu++){
			$sum_hours .= '<td></td>';
		}
		$sum_hours .= '<td align=right style="color:#000000;"><div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($sum,2,'.','').'</font></div></td></tr>';
		
		return $sum_hours;
	
    }  
	//Custom Timesheet 
	function displayTimesheetDetailsEmail_CUSTOM($sno, $mode,$condinvoice ='',$conjoin='', $module='')
	{
		$table = '<div id="grid_form" class="grid_forms" style="white-space:nowrap">';
		$table .= '<table cellspacing="1" cellpadding="5" width="100%"  border=0 style="text-align:left;"> ';
			
		// get the timesheet details
		$data = $this->getTimesheetDetails($sno, $mode,$condinvoice,$conjoin,$module,'Custom');

		foreach($data as $val)
		{

			$usernamedb = $val['username'];
			$servicedateto = $val['penddate'];
			$servicedate = $val['pstartdate'];
		}
			$this->getAssignments($usernamedb, '', $servicedate, $servicedateto, '0');
			$ratesArr =     $this->getRateTypesForAllAsgnnames($this->assignments);
			$headerArr = array('Date', 'Assignments');
		
		array_push($headerArr, $this->getCustomHeaders());
		array_push($headerArr, 'Type');
		$headerCount = count($headerArr);
		$ratetype = $this->getRateTypes();
		$rateCount = count($ratesArr);
		foreach($ratetype as $val)
		{
		    if(in_array($val['rateid'], $ratesArr))
		    {
			array_push($headerArr, $val['name']);
		    }
		}
		/////////////////////////// Main Headers ///////////////////////
		
		$table .= $this->buildMainHeaders($headerArr,$mode);
		//////////////////////// Sub Headers (Hour & Billable) ////////
		$table .= $this->buildSubHeaders($headerArr, $headerCount,$mode,'UOM');
		
		
		foreach($data as $key=>$val)
		{
		    //////////////////////// Sub Headers (Hour & Billable) ////////
			$table .= $this->buildRow_Custom($val, $key, $rateCount,$mode, $module, $data,$print=True,'Custom');
		}
		////////////// Total hours //////////
		
		$table .= $this->getUOMSumRowEmail_CUSTOM($data,$headerArr, 'Custom');
		
		if($mode != 'pending' && $mode != 'errejected' && $mode != 'erer')
		$count = count($headerArr) +2;
		else if($mode == 'pending')
		$count = count($headerArr);
		else if($mode == 'errejected' || $mode =='erer')
		$count = count($headerArr) - 1;
		//////////////////////////// Submitted date ////////////////
		$table .= '<tr class=hthbgcolor><td colspan='.$count.' class="nowrap"><font class=afontstylee>Submitted Date:&nbsp;<b>'.$data[0]['starttimedate'].'</font></td>';
		if($mode == 'errejected' || $mode == 'erer')
		{
		$table .= '<td colspan='.$count.' class="nowrap"><font class=afontstylee>'.'</font></td>';
		}
		$table .='</tr>';
		$table .= '</table> ';
		$table .= '</div>';
				
		return $table;	
	}
	function buildRow_Custom($data, $rowid, $rateCount,$mode, $module='', $alldata='', $print='',$ts_type='')
    {
	
	$arrMode = array('approved' => 'Approved','exported' => 'Approved','rejected' => 'Rejected','deleted' => 'Deleted');
	
	$class = $this->getClasses(" AND sno = $data[classid]");
	$str = '';
	$str .= '<tr>';
	
	////////////////////////////// Check box ////////////////
	if($print===''){
		if($module=='MyProfile'){
		$str .= '<td  valign="top"><input type="checkbox" onclick="chk_clearTop_TimeSheet()" value="'.$data['sno'].'" id="chk'.$rowid.'" name="auids[]" checked="checked"  class="cb-element" style="display:none;"></td>';
		}else{
			if($mode=='approved' || $mode=='exported' || $mode=='deleted'){
				$str .= '<td valign="top"><input type="checkbox" onclick="chk_clearTop_TimeSheet()" value="'.$data['sno'].'" id="chk'.$rowid.'" name="auids[]" checked="checked"  class="cb-element" style="display:none;"></td>';
			}else{
				$str .= '<td valign="top"><label class="container-chk"><input type="checkbox" onclick="chk_clearTop_TimeSheet()" value="'.$data['sno'].'" id="chk'.$rowid.'" name="auids[]" checked="checked"  class="cb-element" "'.$style.'"><span class="checkmark"></span></label></td>';
			}
			
		}
	}
	

	/////////////////////////// Dates //////////////////////
	 if($data['enddate'] !='00/00/0000')
		$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.$data['startdate'].' - '.$data['enddate'].'</font></td>';
	else
		$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.$data['startdate'].' '.$data['weekday'].'</font></td>';
	///////////////////////////// Assignment /////////////////////////////
			if($print===''){
				$str .= '<td width="28%" align="left" style="white-space:inherit !important;word-break:break-all;"><span class="nowrap"><font class=afontstylee>'.$data['cname'].' ('.$data['assid'].') - '.$data['project'].'</span><br/><b>Task Details :</b> '.wordwrap($data['task'], 60, "\n", true);
				$str .='</font></td>';
			}else{
				$str .= '<td width="28%" align="left" style="white-space:inherit !important;word-break:break-all;"><span class="nowrap"><font class=afontstylee>'.$data['cname'].'('.$data['assid'].') - '.$data['project'].'</span><br/><font class=afontstylee><b>Task Details:</b>'.wordwrap($data['task'], 60, "\n", true);
				$str .='</font></td>';
			}
			/////////////////////////// PERSONS ////////////////////////////////
			if($ts_type == 'Custom') {
				
				$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.ucfirst($data['fname']).' '.ucfirst($data['lname']).'</font></td>';   
				$str .= '<td class="nowrap" valign="top"><font class=afontstylee>'.ucfirst($data['typeName']).'</font></td>';	
			}
			elseif(MANAGE_CLASSES == 'Y')
			{
				$str .= '<td class="nowrap"><font class=afontstylee>'.$class[0]['classname'].'</font></td>';
			}
	/////////////////////// Rate types ///////////////
	    
	// To handle a use case where there are multiple values for single ratetype and rowid is also same		
	$data['time_data'] = $this->getDetailsBySnos($data['sno']);			
	//////////////////////////////////////////////////
	
	$str .= $this->getRatevalues($data['time_data'], $rateCount, $alldata);
if($mode != 'pending' && $mode !='errejected' && $mode !='erer')
	  {
	    
	     if($mode == 'approved' || $mode=='backup') {
	     if($data['utype']=="cllacc" && $data['auser']!="")
                    {
                        if($data['status']=="Approved" || $data['status']=="Billed")
						{
							if($data['status']!="Billed" && $data['payroll'] == '')
                           		$disSource="Self Svc (".$data['name'].")";
							else
								$disSource="Self Svc (".$data['name'].") (Billed)";
                        }
						if($data['status']=="Rejected")
                            $disSource="Rejected (".$data['name'].")";
						
                    }
                    else if($data['utype']!="cllacc" && $data['auser']!="")
                    {
						if($data['status']=="Approved" || $data['status']=="Billed")
						{
							if($data['status']!="Billed" && $data['payroll'] == '')
                           		$disSource="Accounting (".$data['name'].")";
							else
								$disSource="Accounting (".$data['name'].") (Billed)";
                        }                        
                        if($data['status']=="Rejected")
                            $disSource="Rejected (".$data['name'].")";
						
                    } else 
					    $disSource = $data['name'];
					$data['name'] =$disSource;
				}
		if(trim($mode) != 'pending' && trim($mode) !='errejected' && trim($mode) !='erer' && trim($mode) !='create' && trim($mode) !='Saved' && trim($mode) != '' && trim($mode)!= 'backup')
		{
	        $str .= '<td  class="nowrap" valign="top"><font class=afontstylee>'.$data['name'].'</font></th>';
		$str .= '<td  class="nowrap" valign="top"><font class=afontstylee>'.$data['approvetime'].'</font></td>';
		}
	  }	
	$str .= '</tr>';
	//$str .= '<tr>';
		
	return $str;
    }
    function getUOMSumRowEmail_CUSTOM($data,$headerArr='', $ts_type = '')
    {
		$rate_totalsort_arr = array();
		$rate_totalunit_arr = array();
		$ratetypes_ids = array();
		$rate_inc ='';
		
		$sum_hours ='';
		
		$headerArr_rates=array();
		$headerArr_rates = $headerArr;
		array_shift($headerArr_rates);
		$header_rates= implode(',',$headerArr_rates);
		$header_rates=str_replace(",","','",$header_rates);
		
		$rate_que = "select sno,rateid,name from multiplerates_master where name IN ('".$header_rates."')";
		$rate_que_sel=$this->mysqlobj->query($rate_que,$this->db);
	
		while($rate_que_myrow=$this->mysqlobj->fetch_array($rate_que_sel))
		{
			$rate_que_myrow['rateid'] =substr($rate_que_myrow['rateid'],4);
			$ratetypes_ids[$rate_que_myrow['rateid']] = $rate_que_myrow['name'];			
		}
		
		$count = count($data);
		$sum = 0;
		$uomDay_sum = 0;
		$uomMile_sum = 0;
		$uomUnit_sum = 0;
		$hourSum = 0;
		$hr_inc = 0;$day_inc = 0;$mile_inc = 0;$unit_inc = 0;
		for($i = 0; $i < $count; $i++)
		{
			$rateArr=array();
			$rate_unit_arr = array();
			
			$ratesSel = explode(',',$data[$i]['time_data']);
			
			
			//Multiple rates array START
			if($data[$i]['mulrates'] !=''){
				$multipleRates = explode('&&',$data[$i]['mulrates']);
				for($p=0;$p<count($multipleRates);$p++){
					$getType = explode('^^',$multipleRates[$p]);
					$rate_unit_arr[$getType[0]]['rate_type'] = $getType[1];
					
				}
			}
			for($r=0;$r<count($ratesSel);$r++){
				$rateVal = explode('|',$ratesSel[$r]);
				$rateArr[$rateVal[0]] = $rateVal[1];
				$rate_unit_arr[$rateVal[0]]['units_cover'] = $rateVal[1];
			}
			
			if($rate_unit_arr){
				foreach($rate_unit_arr as $rate_key=>$rate_val){
					
					if($rate_val['rate_type'] == 'UOM_MILE'){
						$rate_totalunit_arr[$rate_key]['total_miles'] += $rate_val['units_cover'];
						$mile_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_DAY'){
						$rate_totalunit_arr[$rate_key]['total_days'] += $rate_val['units_cover'];
						$day_inc++;
					}
					elseif($rate_val['rate_type'] == 'UOM_UNIT'){
						$rate_totalunit_arr[$rate_key]['total_units'] += $rate_val['units_cover'];
						$unit_inc++;
					}
					else {
						$rate_totalunit_arr[$rate_key]['total_hours'] += $rate_val['units_cover'];
						$hr_inc++;
					}
					$rate_inc =substr($rate_key,4);
					$rate_totalsort_arr[$rate_inc] = $rate_totalunit_arr[$rate_key];
				}
				
			}
			
			$sum = $sum + $data[$i]['sumhours'];
		}
		foreach($ratetypes_ids as $ratetypes_ids_key=>$ratetypes_ids_val){
			if(!array_key_exists($ratetypes_ids_key,$rate_totalsort_arr)){
				$rate_totalsort_arr[$ratetypes_ids_key]=array();
			}
		}
		ksort($rate_totalsort_arr);
		
		///////////////////////////// Total hours ////////////////////
		$sum_hours .= '<tr class=hthbgcolor><td colspan="1" style="color:#000000;">';
		$sum_hours .= '<font class=afontstyle>&nbsp;</font></td>';
		if(in_array('Class',$headerArr)){
			$sum_hours .= '<td></td>';
		}elseif($ts_type == 'Custom'){			
			$sum_hours .= '<td></td><td></td>';
		}
		$sum_hours .= '<td align=right style="color:#000000;">';
		if($hr_inc != 0){
			$sum_hours .= '<div><font class=afontstylee>Total Hours: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($day_inc != 0){
			$sum_hours .= '<div><font class=afontstylee >Total Days: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($mile_inc != 0){
			$sum_hours .= '<div><font class=afontstylee >Total Miles: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		if($unit_inc != 0){
			$sum_hours .= '<div><font class=afontstylee >Total Units: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div>';
		}
		$sum_hours .= '</td>';
		foreach($rate_totalsort_arr as $total_key => $total_val){
			$sum_hours .= '<td align=right style="color:#000000;">';
			
				if($total_val['total_hours'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_hours'],2,'.','').'</font></div>';
				}
				else if($hr_inc != 0 ){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
				if($total_val['total_days'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_days'],2,'.','').'</font></div>';
				}
				else if($day_inc != 0 ){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
				if($total_val['total_miles'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_miles'],2,'.','').'</font></div>';
				}
				else if($mile_inc != 0 ){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
				if($total_val['total_units'] != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($total_val['total_units'],2,'.','').'</font></div>';
				}
				else if($unit_inc != 0){
					$sum_hours .= '<div style="text-align:right;padding-right: 25px;"><font class=afontstylee >0.00</font></div>';
				}
			
			$sum_hours .= '</td>';
		}
		$sum_hours .= '</tr>';
		$sum_hours .= '<tr class=hthbgcolor><td colspan="1"></td>';
		if(in_array('Class',$headerArr)){
			$sum_hours .= '<td></td>';
			$tu =4;
		}else{
			$tu =4;
		}
		if($ts_type == 'Custom'){			
			$sum_hours .= '<td></td><td></td>';
		}
		$sum_hours .= '<td style="color:#000000;" align="right"><div><font class=afontstylee>Grand Total: &nbsp;&nbsp;</font><font class=afontstylee>&nbsp;</font></div></td>';
		for($tu;$tu<count($headerArr)-1;$tu++){
			$sum_hours .= '<td></td>';
		}
		$sum_hours .= '<td align=right style="color:#000000;"><div style="text-align:right;padding-right: 25px;"><font class=afontstylee >'.number_format($sum,2,'.','').'</font></div></td></tr>';
		
		return $sum_hours;
	
    }
    function getCustomTimesheetNotes($parid,$mode,$condinvoice ='',$EmpCompanyName='',$logopath=''){
		global  $accountingExport;
		$person_parsno = array();
		$notes_arr = array();
		$notesCount = 0;		
		$logopathStr='';
		if ($logopath !="") {
			$logopathStr = '<div><img src="'.$logopath.'" border=0 height=48 width=165></div>';
		}else{
			$logopathStr = '<div style="width:165px;height:48px;">&nbsp;</div>';
		}

		$table = "<style>@page { margin: 0px, 35px, 35px, 35px; }</style>";
		$table .= '<table width="99%" border="0" cellspacing="0" cellpadding="0">
					  <tr>
						<td width="50%" align="left" valign="top"><div></div></td>
						<td width="50%" align="right">&nbsp;</td>
					  </tr>
					   <tr>
						<td width="50%" align="left" valign="top">'.$logopathStr.'</td>
						<td width="50%" align="right">&nbsp;</td>
					  </tr>
					  <tr>
						<td><font class=afontstylee>Company Name : <b>'.stripslashes($EmpCompanyName).'</b></font></td>
						
					  </tr>
					  <tr>
						<td width="50%" align="left" valign="top"><div></div></td>
						<td width="50%" align="right">&nbsp;</td>
					  </tr>
					  <tr>
						<td><font class=afontstylee><b>Custom Timesheet Notes</b></font></td>
					  </tr>
					  <tr>
						<td width="50%" align="left" valign="top"><div></div></td>
						<td width="50%" align="right">&nbsp;</td>
					  </tr>
					 </table>
					
					';
		$table .= '<div id="grid_form" class="grid_forms" style="white-space:nowrap">';
		
		if($accountingExport == 'Exported' ) {
			$modeArr = array('pending'=>' and th.status ="ER"','approved' =>' AND th.status IN ("Approved","Billed") and th.exported_status !="YES"','approvedexp' =>' AND th.status IN ("Approved","Billed")','exported' =>' AND th.status IN ("Approved","Billed") and th.exported_status ="YES"','deleted'=>' and th.status IN ("Deleted")','rejected'=>' and th.status IN ("Rejected")','backup'=>' and th.status IN ("Backup")','errejected'=>' and th.status IN ("Rejected")','erer'=>' and th.status IN ("ER")');
		} else {
			 $modeArr = array('pending'=>' and th.status ="ER"','approved' =>' AND th.status IN ("Approved","Billed") ','exported' =>' AND th.status IN ("Approved","Billed") and th.exported_status ="YES"','deleted'=>' and th.status IN ("Deleted")','rejected'=>' and th.status IN ("Rejected")','backup'=>' and th.status IN ("Backup")','errejected'=>' and th.status IN ("Rejected")','erer'=>' and th.status IN ("ER")','approvedexp' =>' AND th.status IN ("Approved","Billed")');

		}
	
		 $timehrs_que = "select th.sno,th.sdate,th.edate,th.hourstype,th.assid,th.rowid,th.person_id
		, ".tzRetQueryStringDate('th.edate','Date','/')." AS enddate, DATE_FORMAT( th.sdate, '%W' ) AS weekday,".tzRetQueryStringDate('th.sdate','Date','/')." AS startdate,CONCAT(person_info.fname,' ',person_info.lname) as personName
		from timesheet_hours th left join person_info on person_info.sno = th.person_id and person_info.status !='Backup' where th.parid='".$parid."' ".$condinvoice."  ".$modeArr[$mode]."  order by    th.sdate ASC ";
		
		$timehrs_res = $this->mysqlobj->query($timehrs_que,$this->db);
		$zrowCount = mysql_num_rows($timehrs_res);
		while($timehrs_row = $this->mysqlobj->fetch_array($timehrs_res)){
			
			$ts_notes_que = "select tssno,notes,tsrowid,person_id,tshourstype from person_ts_notes where parid='".$parid."' and tssno IN (0,".$timehrs_row['sno'].") and tsrowid='".$timehrs_row['rowid']."' and status!='Backup' and notes!='' order by tsrowid ASC,tssno ASC ";
			
			$ts_notes_res = $this->mysqlobj->query($ts_notes_que,$this->db);
			$zrowCount1 = mysql_num_rows($ts_notes_res);
			if($zrowCount1 > 0){
				$notesCount = $notesCount+1;
			}
			
			while($ts_notes_row = $this->mysqlobj->fetch_array($ts_notes_res)){
			
				if($ts_notes_row['tshourstype']=='person' && $ts_notes_row['tsrowid']==$timehrs_row['rowid'] && $ts_notes_row['tssno'] == 0 && !in_array($ts_notes_row['tsrowid'],$person_parsno)){
					$table .= '<div><div id="leftdiv" style="float:left;width:25%;"><font class=afontstylee>';
					if($timehrs_row['enddate'] !='00/00/0000'){
						$table .= $timehrs_row['startdate'].' - '.$timehrs_row['enddate'];
					}else{
						$table .= $timehrs_row['startdate'].' '.$timehrs_row['weekday'];
					}
					$table .= '&nbsp;&nbsp;&nbsp;';
					$table .= $timehrs_row['assid'];
					$table .= '&nbsp;&nbsp;&nbsp;';
					$table .= $timehrs_row['personName'].' Notes';
					$table .= '&nbsp;&nbsp;&nbsp;';
					$table .= '</font></div>';
					$table .= '<div id="rightdiv" style="float:right;width:70%;"><font class=afontstylee>';
					$table .= $ts_notes_row['notes'];
					$table .= '</font></div></div><div class="clear" style="clear:both;margin:10px"></div>';
					$person_parsno[]=$ts_notes_row['tsrowid'];
					
				}
				if($ts_notes_row['tshourstype']==$timehrs_row['hourstype'] && $ts_notes_row['tsrowid']==$timehrs_row['rowid'] && $ts_notes_row['tssno'] == $timehrs_row['sno']){
					$table .= '<div><div id="leftdiv" style="float:left;width:25%;"><font class=afontstylee>';
					if($timehrs_row['enddate'] !='00/00/0000'){
						$table .= $timehrs_row['startdate'].' - '.$timehrs_row['enddate'];
					}else{
						$table .= $timehrs_row['startdate'].' '.$timehrs_row['weekday'];
					}
					$table .= '&nbsp;&nbsp;&nbsp;';
					$table .= $timehrs_row['assid'];
					$table .= '&nbsp;&nbsp;&nbsp;';
					$rateName_que = "select name from multiplerates_master where rateid='".$ts_notes_row['tshourstype']."' limit 1";
					$rateName_res = $this->mysqlobj->query($rateName_que,$this->db);
					$rateName_row = $this->mysqlobj->fetch_row($rateName_res);
					$table .= $rateName_row[0].' Notes';
					$table .= '&nbsp;&nbsp;&nbsp;';
					$table .= '</font></div>';
					$table .= '<div id="rightdiv" style="float:right;width:70%;"><font class=afontstylee>';
					$table .= $ts_notes_row['notes'];
					$table .= '</font></div></div><div class="clear" style="clear:both;margin:10px"></div>';
				
				}
			}
		
		}
		$table .='</div>';
		$notes_arr[0] = $notesCount;
		$notes_arr[1]=$table;
		return $notes_arr;
	}
    // get Client preferences based on the passing module- CSS User
    function getClientPrefs($username)
    {	    
	    $sqlSelfPref		= 	"select sno, username, joborders, candidates, assignments, placements, billingmgt, timesheet, invoices, expenses, joborders_owner from selfservice_pref where username='".$username."'";
	    $resSelfPref		= 	mysql_query($sqlSelfPref,$this->db);
	    $userSelfServicePref	=	mysql_fetch_array($resSelfPref);
	    return $userSelfServicePref;
    }
    
    function checkAssignmentClients($employee, $assignStartDate0, $assignEndDate0, $billcontact='')
    {
		$assignStartDate = date('Y-m-d', strtotime($assignStartDate0));
		$assignEndDate = date('Y-m-d', strtotime($assignEndDate0));
		
		if(!empty($billcontact))
		{
			$billingcontactcond = "AND hrcon_jobs.bill_contact='".$billcontact."'";
		}
		$zque = "SELECT GROUP_CONCAT(DISTINCT hrcon_jobs.client) FROM hrcon_jobs WHERE username = '".$employee."' AND pusername!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$assignEndDate."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$assignStartDate."'),1))) AND hrcon_jobs.jtype!='' $billingcontactcond";
		
		$zres		= $this->mysqlobj->query($zque,$this->db);
		$zrow 	= mysql_fetch_row($zres);
		return $zrow[0];
    }
    	//Following function used to identify any timesheet is filling for all clients or not in the last month
	function getTimesheetFillingStatus($username)
	{ 
		$last_month_start = date("Y-m-01", strtotime("last month"));
		$last_month_end = date("Y-m-t", strtotime("last month"));
		$sel="SELECT payperiod,lockdown_restrictions FROM cpaysetup WHERE STATUS='ACTIVE'";
		$res=$this->mysqlobj->query($sel, $this->db);
		$getPayPeriod= $this->mysqlobj->fetch_array($res);
		$payperiod = $getPayPeriod['payperiod'];
		$statusFlag = FALSE;
		if($payperiod == 'Monthly')
		{
			 
			$timesheet_check_prev_month = "SELECT hj.username,hj.client,hj.pusername ,count(ts.parid) as tscount,ts.parid
			FROM hrcon_jobs as hj 
			LEFT JOIN timesheet_hours as ts ON (ts.username = hj.username and ts.client = hj.client AND ts.sdate >='".$last_month_start."' AND ts.sdate <='".$last_month_end."'  and ts.status NOT IN('Backup','Deleted'))
			WHERE hj.username = '".$username."' 
			AND ((hj.ustatus IN ('active','closed','cancel')  
			AND (hj.s_date IS NULL OR hj.s_date='' OR hj.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$last_month_end."')))  
			AND (IF(hj.ustatus='closed',(hj.e_date IS NOT NULL AND hj.e_date<>'' AND hj.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$last_month_start."'),1))  AND (IF(hj.ustatus='cancel',(hj.e_date IS NOT NULL AND hj.e_date<>'' AND hj.e_date<>'0-0-0' AND hj.e_date <> hj.s_date AND DATE(STR_TO_DATE(hj.e_date,'%m-%d-%Y'))>='".$last_month_start."'),1)))  
			AND hj.jtype!=''
			GROUP BY hj.client" ;
			
			$timesheet_details_res = $this->mysqlobj->query($timesheet_check_prev_month, $this->db);
			while($timesheet_details=$this->mysqlobj->fetch_array($timesheet_details_res))
			{
				if($timesheet_details['tscount'] == '0'){
					
					$statusFlag = TRUE;
					break;
				}else{
					
					$statusFlag = FALSE;
				}
			}
		}
		return $statusFlag;
	}
}
?>