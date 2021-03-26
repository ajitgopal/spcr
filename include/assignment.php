<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<?php
/*
*
*
Modified Date: September 29, 2015.
Modified By  : Rajesh kumar V.
Purpose		 : Added select2 jquery libraries and css files for providing search functionality to the job locations select list.
Task Id		 : #800717 [IMI -Multiple Locations]
*
*
*/
	$connames="";
	$repnames="";
	$newRowId=0;
	require_once("dispfunc.php");
	require($app_inc_path."displayoptions.php");
	require_once($akken_psos_include_path.'commonfuns.inc');
	require_once("multipleRatesClass.php");
	require_once("nusoap.php");
	//require_once("../../Include/class.NetPayroll.php");

	/*
		Perdiem Shift Scheduling Class file
	*/
	require_once('perdiem_shift_sch/Model/class.hrm_perdiem_sch_db.php');
	$hrmPerdiemShiftSchObj = new HRMPerdiemShift();

	/* including employees db class file */
	require_once("shift_schedule/hrm_schedule_db.php");
	$objHRMScheduleDetails	= new EmployeeSchedule();
	
	/* including shift schedule class file for converting Minutes to DateTime / DateTime to Minutes */
	require_once('shift_schedule/class.schedules.php');
	$objSchSchedules	= new Schedules();
	
	//Calling function for current date
	$cur_day=mySQLNowDate("Unix");	
	$thisday=$cur_day[0];//mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"));
	$CUR_DISP_DATE=date("m/d/Y",$thisday);
	$todate=date("Y-m-d H:i:s",$cur_day[1]);
	$disp_cur_date="";	
	$disp_date=explode("^AKK^",$elements[59]);
	$ratesObj = new multiplerates();
	$displaytypes = $objMRT->displayPayType();

        $mode_rate_type = "joborder";
	$type_order_id = $pos;
	if(isset($copyasign)){
		$copyasign = $copyasign;
	}else{
		$copyasign ="no";
	}
	if(isset($fromGigboard)){
		$fromGigboard = $fromGigboard;
	}else{
		$fromGigboard ="no";
	}
	$recassignVal			= explode("|",$recno);
	$burden_status 			= getBurdenStatus();
	$ratesDefaultVal['Regular'] 	= array($elements[81],$elements[82]);
	$ratesDefaultVal['OverTime'] 	= array($elements[83],$elements[84]);
	$ratesDefaultVal['DoubleTime'] 	= array($elements[85],$elements[86]);

$br_alert_flag = 0;
$perdiemalertChk = 0;
if($modfrom == "updateasgmt" && $showAssignid != "")
{	
	//getting the invoice sno values fro mthe timesheet hours which are billed
	$get_inv_sno_sql = "SELECT  DISTINCT billable,
								hourstype,
								perdiem_billed
							FROM timesheet_hours 
							WHERE billable != '' AND 
							billable NOT IN ('Yes','No') AND 
							assid = '".$showAssignid."' AND
							status = 'Billed'
							";
	$get_inv_sno_res=mysql_query($get_inv_sno_sql,$db);	
	$invsnoArray = array();
	$invRTArray = array();
	$notDelInvSnoArray = array();
	while($thinvdet = mysql_fetch_row($get_inv_sno_res))
	{		
		$invsnoArray[] = $thinvdet[0];	
		if($thinvdet[2] == 'billable')
		{
			$perdiemalertChk = 1;
		}
	}
	$invsnoArray = array_unique($invsnoArray);	
	$invSnoStr = implode(",",$invsnoArray);
	
	if($invSnoStr != "")
	{
		//checking whether any invoice is in deliver invoice section
		$chk_delinv_sql = "SELECT count(1) FROM invoice WHERE deliver='no' AND sno IN (".$invSnoStr.") AND status='ACTIVE'";
		$chk_delinv_res=mysql_query($chk_delinv_sql,$db);
		$delinvdet =  mysql_fetch_row($chk_delinv_res);
		if($delinvdet[0] > 0)
		{
			$br_alert_flag = 1;			
		}				
	}
	//overwrite to not show the alert for per diem when invoice is not in delivery invoice section
	if($br_alert_flag == 0)
	{
		$perdiemalertChk = 0;
	}
}
	if($modfrom=="newasgmt" || $mode=="newassign" ||  $modfrom=="applicant" ||  $modfrom=="hiring") //IF It is a new assignment or if it comming from Applicant tracking edit assignment page and created date is empty
	{
		if($elements[36]=='')
		{
			$elements[59]=$CUR_DISP_DATE."^AKK^".$todate;
			$disp_cur_date=$CUR_DISP_DATE;
		}
		else
		{
			if($disp_date[1]=="" || $disp_date[1]=="0000-00-00 00:00:00")
			{			
				$elements[59]=$CUR_DISP_DATE."^AKK^".$todate;
				$disp_cur_date=$CUR_DISP_DATE;
			
			}
			else
			{
				$elements[59]=$disp_date[0]."^AKK^".$disp_date[1];
				$disp_cur_date=$disp_date[0];
			}
		}
	}
	else
	{
		if($disp_date[0]!="" && $disp_date[0]!="0000-00-00 00:00:00")
		{
			$disp_cur_date=$disp_date[0];
		}
	}
	if($copyasign == "yes"){
		$elements[59]=$CUR_DISP_DATE."^AKK^".$todate;
		$disp_cur_date=$CUR_DISP_DATE;
	}
	//Bill Rate Information.
	$billValue=explode("^^",$elements[24]);
	$billRateVal=$billValue[0];
	$billPerVal=$billValue[1];  //changed index from 2-->1   BY D.PRASAD
	$billCurVal=$billValue[2];  //changed index from 1-->2

	//Pay Rate Information.	
	$payValue=explode("^^",$elements[25]);
	$displayCopy = 'Yes';
	//Sunil Commented 
	if(isset($elements[2])){
		$que1="select sno,name,type from manage where (type='jotype' or type='jocategory') and sno = ".$elements[2];
		$res1=mysql_query($que1,$db);
		$resRow = mysql_fetch_array($res1); 
		if( ($resRow[1] == "Internal Temp/Contract") || ($resRow[1] == "Temp/Contract") || ( $resRow[1] == "Temp/Contract to Direct")){	
			$payRateVal=$payValue[0];
			$payPerVal=$payValue[1];  //changed index from 2-->1
			$payCurVal=$payValue[2];  //changed index from 1-->2
		}
		if(($resRow[1] == "Internal Direct") || ($resRow[1] == "Direct")){
			$displayCopy = 'No';
		}
	}	
	//Pay Open and Bill Open Information
	$payFinal = explode("^^",$elements[29]);

	//To focus tab(rate,margin,markup)
	if($payFinal[0]=='MN')
		$tabStatus="margin";
	else if($payFinal[0]=='MP')
		$tabStatus="markup";
	else if($payFinal[0]=='R' || $payFinal[0]=='')
		$tabStatus="rates";
	//END

	if($payFinal[1]=="")
		$payStat="checked";

	if($payFinal[2]=="")
		$billStat="checked";

	//Job order sno
	$posid=explode("|",$addr);
	$pos=$posid[0];

	//Query to get data of contact and company of a Assignment
	if($elements[8]!='0' && $elements[8]!='')
	{ 
		$contact_que="SELECT staffacc_cinfo.cname, CONCAT_WS( ' ', staffacc_contact.fname, staffacc_contact.mname, staffacc_contact.lname ) ,staffacc_cinfo.sno,staffacc_cinfo.address1,staffacc_cinfo.address2,staffacc_cinfo.city,staffacc_cinfo.state,'',staffacc_cinfo.bill_req,staffacc_cinfo.service_terms,staffacc_cinfo.username,staffacc_contact.sno FROM staffacc_contact LEFT JOIN staffacc_cinfo on staffacc_contact.username = staffacc_cinfo.username AND staffacc_cinfo.type IN ('CUST','BOTH') LEFT JOIN staffacc_list ON staffacc_list.username = staffacc_cinfo.username WHERE staffacc_contact.sno ='".$elements[8]."' AND staffacc_list.status = 'ACTIVE' and staffacc_contact.acccontact='Y' and staffacc_contact.username!=''";
		$res_contact=mysql_query($contact_que,$db);
		$contact_fetch=mysql_fetch_row($res_contact);
		$cont_num_rows=mysql_num_rows($res_contact);
		if($cont_num_rows == 0)
			$elements[8] = 0;
	}

	if($elements[9]!='0')
	{
		$company_que="SELECT staffacc_cinfo.cname, '' ,staffacc_cinfo.sno,staffacc_cinfo.address1,staffacc_cinfo.address2,staffacc_cinfo.city,staffacc_cinfo.state,'',staffacc_cinfo.bill_req,staffacc_cinfo.service_terms,staffacc_cinfo.username, staffacc_cinfo.vprt_GeoCode, staffacc_cinfo.vprt_State,staffacc_cinfo.vprt_County, staffacc_cinfo.vprt_Local, staffacc_cinfo.zip  FROM staffacc_cinfo  LEFT JOIN staffacc_list ON staffacc_list.username = staffacc_cinfo.username WHERE  staffacc_cinfo.sno ='".$elements[9]."' AND staffacc_list.status = 'ACTIVE' AND staffacc_cinfo.type IN ('CUST','BOTH')";
		$res_company=mysql_query($company_que,$db);
		$company_fetch=mysql_fetch_row($res_company);
		$comp_num_rows=mysql_num_rows($res_company);

		if($company_fetch[11] == '' || $company_fetch[11] == '0')
		{
			$argElements = array("EntityType"=>'Customer',"EntityId"=>$company_fetch[2],"EntityZip"=>$company_fetch[15]);
			//$OBJ_NET_PAYROLL = new NetPayroll();
			//$company_fetch[11] = $OBJ_NET_PAYROLL->setEntityGeo($argElements);				
		}
	}
	//Query to get the contacts of a related company
	$con_rows=0;

	if($elements[9]!='0' && $comp_num_rows > 0)
	{

		$con_sel_rows=0;

		$con_names="SELECT staffacc_contact.sno, CONCAT_WS( ' ', staffacc_contact.fname, staffacc_contact.mname, staffacc_contact.lname ) FROM staffacc_contact WHERE staffacc_contact.username='".$company_fetch[10]."' and staffacc_contact.acccontact='Y' and staffacc_contact.username!='' order by staffacc_contact.fname, staffacc_contact.mname, staffacc_contact.lname";
		$res_con=mysql_query($con_names,$db);
		$con_rows=mysql_num_rows($res_con);

		while($fetch_con=mysql_fetch_row($res_con))
		{
			$connames.="<option  value=".$fetch_con[0]." ".compose_sel($elements[8],$fetch_con[0])." >".dispTextdb($fetch_con[1])." - ".$fetch_con[0]."</option>";
			$con_sel_rows++;
		}

	}

	// Perdiem Shift Type
	$shift_type ="";
	$perdiem_shift_id=0;
	if (isset($elements[0])) {
		$selectShiftType = "SELECT shift_type,shiftid FROM hrcon_jobs WHERE sno='".$elements[0]."'";
		$resultShiftType = mysql_query($selectShiftType);
		$shiftTypeInfo = mysql_fetch_row($resultShiftType);
		$shift_type = $shiftTypeInfo[0];
		$perdiem_shift_id = $shiftTypeInfo[1];
	}
	if($modfrom=='hiring' && isset($conjob_sno))
	{
		$selectShiftType="select shift_type,shiftid from consultant_jobs where sno='".$conjob_sno."'";
		$resultShiftType = mysql_query($selectShiftType);
		$shiftTypeInfo = mysql_fetch_row($resultShiftType);
		$shift_type = $shiftTypeInfo[0];
		$perdiem_shift_id = $shiftTypeInfo[1];
		$pageFrom = "consultant";
	}

	$sdate=explode("-",$elements[12]);
	$hrdate=explode("-",$elements[13]);
	$date=explode("-",$elements[18]);
	$edate=explode('-',$elements[16]);
	$startdate=$sdate[1]."/".$sdate[2]."/".$sdate[0];
	$eenddate=$hrdate[1]."/".$hrdate[2]."/".$hrdate[0];
	$enddate=$edate[1]."/".$edate[2]."/".$edate[0];

	//StartDate, EndDate, ExpectedEndDate years, 20 years for past years and 10 years to future.
	$startyear = displayPastFutureYears($sdate[2]);
	$eendyear = displayPastFutureYears($hrdate[0]);
	$endyear = displayPastFutureYears($edate[2]);	
	
	//Query to get the employees
	// commeted the internal employees
	$que="SELECT e.username AS username, e.name AS name, e.sno AS ID
		  FROM emp_list e 
		  LEFT JOIN users u ON (u.username = e.username)
		 WHERE  e.lstatus != 'DA'
		       AND e.lstatus != 'INACTIVE'
		       AND u.status!='DA' 
		       AND u.usertype!='' 
		ORDER BY e.name"; 

	$res=mysql_query($que,$db);

	//Query to get the recruiter/Vendor
	$que_rec="SELECT staffacc_contact.sno, CONCAT_WS( ' ', staffacc_contact.fname, staffacc_contact.mname, staffacc_contact.lname ) ,staffacc_contact.username FROM staffacc_contact WHERE staffacc_contact.sno='".$elements[7]."' and staffacc_contact.acccontact='Y' and staffacc_contact.username!=''";
	$res_rec=mysql_query($que_rec,$db);
	$fetch_rec=mysql_fetch_row($res_rec);
	$fetch_rec_rows=mysql_num_rows($res_rec);



	$check_value=explode('-',$elements[44]);

	//displaying Requirements with '-' in Hiring process
	$hirepro=explode("-",$elements[44]);
	$Htype=$elements[14];

	if($modfrom!="newasgmt" || $internalChk=="Checked")
	{
		// checking any active assignments are there are not.
		$que1="select count(1) from hrcon_jobs where ustatus='active' and jtype='OP' and username='$conusername'";
		$result1=mysql_query($que1,$db);
		$rsassign=mysql_fetch_array($result1);

		// checking any active assignments are there are not.
		$que2="select emptype from hrcon_compen where ustatus='active' and username='$conusername'";
		$result2=mysql_query($que2,$db);
		$rscompen=mysql_fetch_array($result2);
		$rsassign=$rsassign[0]."|".$rscompen[0];
	}
	//Function to get the lable name of the person
	function getCustomlabel(){
		$headerqry		= "SELECT person FROM manage_person_details ";
		$headerqry		= mysql_query($headerqry);
		$headerresults	= mysql_fetch_row($headerqry);
		return $headerresults[0];
	}
	function DisplaySchdule($pos,$Htype,$modfrom)
	{
		global $username,$maildb,$db,$user_timezone;

		$RecordArray=array();
		array_push($RecordArray,$Htype);

		//Condition to execute the query when it comes from employee management
		if($modfrom=="empman" || $modfrom=="employee")
		{
			$pos1=$pos."|";
			$que="select appno from assignment_schedule where contactsno like '".$pos1."%' and modulename='HR->Assignments' and invapproved != 'backup'";
		}
		else  if($modfrom=="approve" || $modfrom =="updateasgmt")
		{
			$pos1= $pos."|";
			$que="select appno from assignment_schedule where contactsno like '%".$pos1."' and modulename = 'HR->Assignments' and invapproved != 'backup'";
		}
		else
		{
			$que="select appno from assignment_schedule where contactsno='".$pos."' and modulename='HR->Assignments' and invapproved != 'backup'";
		}
		$res=mysql_query($que,$db);
		$row=mysql_fetch_row($res);

		//Condition to execute the query when it comes from employee management
		if($row[0]!='')
		{
			if($modfrom=="empman" || $modfrom=="employee")
				$query="select sno,if(".tzRetQueryStringSelBoxDate('sch_date','CEYIntDate','/')."='0/0/0000','',".tzRetQueryStringSelBoxDate('sch_date','Date','/')."),if(wdays>0,wdays,''),starthour,endhour from hrcon_tab  where tabsno='".$row[0]."' and coltype='assign' order by sch_date,wdays,starthour desc";
			else  if($modfrom=="approve" || $modfrom=="updateasgmt")
				$query="select sno,if(".tzRetQueryStringSelBoxDate('sch_date','CEYIntDate','/')."='0/0/0000','',".tzRetQueryStringSelBoxDate('sch_date','Date','/')."),if(wdays>0,wdays,''),starthour,endhour from hrcon_tab where tabsno='".$row[0]."' and coltype='assign' order by sch_date,wdays,starthour desc";
			else
				$query="select sno,if(".tzRetQueryStringSelBoxDate('sch_date','CEYIntDate','/')."='0/0/0000','',".tzRetQueryStringSelBoxDate('sch_date','Date','/')."),if(wdays>0,wdays,''),starthour,endhour from consultant_tab  where consno='".$row[0]."' and coltype='assign' order by sch_date,wdays,starthour desc";
		}
		$QryExc=mysql_query($query,$db);

		if(mysql_num_rows($QryExc)>0)
		{
			while($SchRow=mysql_fetch_row($QryExc))
			array_push($RecordArray,implode("|^AkkSplitCol^|",$SchRow));
		}
		return implode("|^AkkenSplit^|",$RecordArray);
	}

	//Function for displaying the history of rates entered
	function displayRatesHistory($assign_id)
	{
		global $db;
		$table_content = "<table width='98%' class='crmsummary-jocomp-table' cellspacing='0' cellpadding='5' border='0' align='left' style='padding-left:0px;'>
			<tr class='summaryform-bold-title'>			
			<th align='left' style='padding-left: 15px;'>Snapshot Date</th>
			<th align='left'>Modified By</th>
			<!--<th align='left'>Rate Type</th>
			<th align='left'>Pay Rate</th>
			<th align='left'>Bill Rate</th>
			<th align='left'>Billable</th>
			<th align='left'>Taxable</th>-->
			<th></th>
			</tr>";
							
			$his_query = "SELECT hhj.sno, ".tzRetQueryStringDTime("hhj.mdate","DateTimeSec","/")." AS 'm_date', hhj.mdate AS 'Modified_Date', u.name AS 'Modified_User'
			FROM his_hrcon_jobs hhj
			LEFT JOIN users u ON u.username = hhj.muser
			WHERE hhj.pusername = '".$assign_id."'
			AND hhj.ustatus = 'backup'
			GROUP BY hhj.sno, hhj.mdate, hhj.muser
			ORDER BY hhj.mdate DESC";
		$his_query_rs = mysql_query($his_query,$db) or mysql_error();
		
		$rows_count = mysql_num_rows($his_query_rs);
		
		if($rows_count=='0')
		{
			$table_content .= "<tr class='summaryform-formelement'><td colspan=4 align=center>No history data</td><td></td>";
		}
		else
		{
			$j = 0;
			while($his_row = mysql_fetch_assoc($his_query_rs))
			{					
				if($j%2==0)
					$class="";
				else
					$class="";
					
				$hisdate  =  $his_row['m_date'];
				$his_sno  =  $his_row['sno'];
				
				if($hisdate=='' || $hisdate=='0000-00-00 00:00:00')
				{
				 $table_content .= "<tr class='summaryform-formelement ".$class."'><td style='padding-left: 15px;'>N/A</td>";
				}
				else{
				//$hisdate  =  $his_row['m_date'];
				$table_content .= "<tr class='summaryform-formelement ".$class."'><td style='padding-left: 15px;'><a style='font-size:11px;text-decoration:underline;' href='javascript:OpenHistory(\"$hisdate\",\"$his_sno\")'>{$hisdate}</a></td>";
				}
				
				//$mod_date	= date('m/d/Y', strtotime($his_row['m_date']));
				
				$table_content .= "<td style='margin-left: 0px; padding-right: 8px; padding-left: 5px;'>{$his_row['Modified_User']}</td>";
				/*$table_content .= "<td>{$his_row['Rate_Type']}</td>";
				$table_content .= "<td>{$his_row['Pay_Rate']}</td>";
				$table_content .= "<td>{$his_row['Bill_Rate']}</td>";
				$table_content .= "<td>{$his_row['Billable']}</td>";
				$table_content .= "<td>{$his_row['Taxable']}</td>";*/
				$table_content .= "<td></td></tr>";
				$j++;				
			}
		}
		echo $table_content;
		
	}
	if($elements[22]!="")
	{
		$defaultsch="";
		$schdet=DisplaySchdule($elements[48],$elements[14],$modfrom);
                if($schdet=='fulltime' || $schdet=='parttime')
                {
                    $defaultsch="fulltime";
			
                }
        }
	else
	{
		if($modfrom=="applicant")
			$schdet=$_SESSION['HRAT_Assgschedule'.$apprn];
		else if($modfrom=="hiring")
			$schdet=$_SESSION['HRHM_Assgschedule'.$apprn];
		else if($modfrom=="empman" || $modfrom=="employee")
			$schdet=$_SESSION['HREM_Assgschedule'.$apprn];
	}

	function addrValue($row1,$row2,$row3)
	{
		$comp_addr="";	
		if($row1!='' && $row2!='')
			$comp_addr=$row1.", ".$row2;
		else if($row1!='') 
			$comp_addr=$row1;	
		else if($row2!='')
			$comp_addr=$row2;

		if($row3!='')
		{
			if($comp_addr!='')
				$comp_addr.=", ".$row3;
			else
				$comp_addr=$row3;			
		}	
		return($comp_addr);
	}

	//Query to get the sno of jobtype to select 'Temp/Contract' as default job type while creating a new assignment
	$job_que="select sno from manage where name='Temp/Contract' and type='jotype'";
	$job_res=mysql_query($job_que,$db);
	$job_row=mysql_fetch_row($job_res);
	$jtype_name=$job_row[0];
	$DispTimes=display_SelectBox_Times();

	//For Export , we need to send these data
	$rec_exp = explode("|",$recno);
	if($modfrom=="approve")
		$rec_status="NeedsApproval";
	else
		$rec_status="active";

	if($modfrom=="empman" || $modfrom=="employee" || $modfrom=="approve" || $modfrom=="updateasgmt") //If it is comming from Employee Management    
		$rec_tblname="hrcon_jobs";
	else
		$rec_tblname="empcon_jobs";

	if($modfrom=="empman" || $modfrom=="employee")
		$rec_sno=$hhid;
	else
		$rec_sno=$rec_exp[0]; 

	$sel_perdiem_cur = $elements[74];
	$sel_perdiem_per = $elements[75];

	if($mode=="newassign")
	{
		if($elements[76]=="")
			$elements[76]="N";  //This field for Per-Diem (billable) - By default selecting as Billable
		if($elements[77]=="")
			$elements[77]="N";  //This field for Per-Diem (taxable) - By default selecting as Taxable
	}

	$padding_style = "style='padding-left:271px;'";
	if(strpos($_SERVER['HTTP_USER_AGENT'],'Gecko') > -1)
	{
		$rightflt = "style= 'width:68px;'";
		$inner_rightflt = "style= 'width:35px;'";
		$style_table = "style='padding:0px; margin:0px;'";
		$padding_style = "style='padding-left:310px;'";
	}

	//Start code for populating Comission roles
	$strDirectInternal = "'BR', 'PR', 'MN', 'MP'"; //Direct or Internal Direct
	$strConTempContact = "'RR'"; //Internal Temp or Temp/Contract
	$strConTempToDirect = "''"; //Temp Contact to Direct
	$rolesSelectIds = array();

	if($strCon != "")
		$condition = " rp.commissionType NOT IN (".$strCon.") OR ";
		
		$queryRoles ="SELECT sno,roletitle  FROM company_commission WHERE status ='active' ORDER BY roletitle,commission_default"; 
		//$queryRoles = "SELECT cs.sno, cs.roletitle FROM company_commission AS cs left join rates_period AS rp ON (cs.sno = rp.parentid AND rp.parenttype = 'COMMISSION') WHERE cs.status = 'active'"; 

	/*$queryRoles = "SELECT cs.sno, cs.roletitle FROM company_commission AS cs left join rates_period AS rp ON (cs.sno = rp.parentid AND rp.parenttype = 'COMMISSION' AND (IF(rp.enddate = '0000-00-00 00:00:00', DATEDIFF(DATE_FORMAT(NOW(),'%Y-%m-%d'),STR_TO_DATE(rp.startdate, '%Y-%m-%d')) >= 0, DATE_FORMAT(NOW(),'%Y-%m-%d') BETWEEN STR_TO_DATE(rp.startdate, '%Y-%m-%d') AND STR_TO_DATE(rp.enddate, '%Y-%m-%d')))) WHERE cs.status = 'active'"; */
	$queryDirectInternal = $queryRoles;
	$resDirectInternal  = mysql_query($queryDirectInternal,$db);
	$lstDirectInternal = $lstTempContact = $lstTempToDirect = "";
	while($rowDirectInternal = mysql_fetch_row($resDirectInternal))
	{
		$rolesSelectIds[] = $rowDirectInternal[0];
		if($lstDirectInternal == '')
			$lstDirectInternal = $rowDirectInternal[0]."^".$rowDirectInternal[1];
		else
			$lstDirectInternal .= "|Akkensplit|".$rowDirectInternal[0]."^".$rowDirectInternal[1];
	}

	$queryTempContact = $queryRoles;
	$resTempContact  = mysql_query($queryTempContact,$db);
	while($rowTempContact = mysql_fetch_row($resTempContact))
	{
		$rolesSelectIds[] = $rowTempContact[0];
		if($lstTempContact == '')
			$lstTempContact = $rowTempContact[0]."^".$rowTempContact[1];
		else
			$lstTempContact .= "|Akkensplit|".$rowTempContact[0]."^".$rowTempContact[1];
	}

	$queryTempToDirect = $queryRoles;
	$resTempToDirect  = mysql_query($queryTempToDirect,$db);
	while($rowTempToDirect = mysql_fetch_row($resTempToDirect))
	{
		$rolesSelectIds[] = $rowTempToDirect[0];
		if($lstTempToDirect == '')
			$lstTempToDirect = $rowTempToDirect[0]."^".$rowTempToDirect[1];
		else
			$lstTempToDirect .= "|Akkensplit|".$rowTempToDirect[0]."^".$rowTempToDirect[1];
	}
	//End code for populating Comission roles

	// Get the taxes for the assignment
	$selectedTaxes='';
	if($mode=="editassign")
	{
		$empid = explode('|',$recno);
		$empid=$empid[3];
		$chkWithHoldingSno="";

		$sqlTaxes = "SELECT taxsno,exempt,emp.vprt_GeoCode, emp.username from vprt_taxhan_emp_apply tax INNER JOIN emp_list emp ON tax.empid=emp.username WHERE tax.apply= 'Y' AND tax.status='A' AND tax.assid='".$showAssignid."' AND emp.sno=".$empid;
		$resTaxes = mysql_query($sqlTaxes,$db);
		if(mysql_num_rows($resTaxes)>0)
		{
			while($rowTaxes = mysql_fetch_row($resTaxes))
			{
				if($rowTaxes[2] == '' || $rowTaxes[2] == '0')
				{
					$getZipEmp = "SELECT zip FROM hrcon_general WHERE username = '".$rowTaxes[3]."' AND ustatus = 'active'";
					$resZipEmp = mysql_query($getZipEmp,$db);
					$rowZipEmp = mysql_fetch_row($resZipEmp);

					$argElements = array("EntityType"=>'Employee',"EntityId"=>$rowTaxes[3],"EntityZip"=>$rowZipEmp[0]);

					//$OBJ_NET_PAYROLL_Geo = new NetPayroll();
					//$rowTaxes[2] = $OBJ_NET_PAYROLL_Geo->setEntityGeo($argElements);				
				}

				// Get Tax ID
				$sql = "SELECT taxid,sno,schdist from vprt_taxhan where sno=".$rowTaxes[0];
				$resSql = mysql_query($sql,$db);
				$row = mysql_fetch_row($resSql);
				if($selectedTaxes=='')
					$selectedTaxes = $row[0].'_'.$row[2]."^".$rowTaxes[0]."^".$rowTaxes[1];
				else
					$selectedTaxes .= '|'.$row[0].'_'.$row[2]."^".$rowTaxes[0]."^".$rowTaxes[1];

				if($row[0] == '450')
					$chkWithHoldingSno=$row[1];	
			}
		}
	}

	
	////---Added Functionality To Auto Select Worker Comp Code When Burdern is selected---///
	$sts_sel = "SELECT autoset_workercomp,payburden_required,billburden_required FROM burden_management";
	$sts_res = mysql_query($sts_sel, $db);
	$sts_rec = mysql_fetch_array($sts_res);
	$autowcc_status = $sts_rec['autoset_workercomp'];
	$payburden_status = $sts_rec['payburden_required'];
	$billburden_status = $sts_rec['billburden_required'];	
	////---End of Code---///


if($burden_status == 'yes'){

    //Getting the burden type and item details
    if($modfrom=="empman" || $modfrom=="employee" || $modfrom=="approve" || $modfrom=="updateasgmt")
    {
            $bt_fetch_table = "hrcon_burden_details";
            $table_id = 'hrcon_jobs_sno';
    }
    /*else if($modfrom=="approve" || $modfrom=="updateasgmt")
    {
            $bt_fetch_table = "empcon_burden_details";
            $table_id = 'empcon_jobs_sno';
    }*/
    else
    {
            $bt_fetch_table = "consultant_burden_details";
            $table_id = 'consultant_jobs_sno';
    }

    if($recassignVal[2] == 'closed' || $recassignVal[2] == 'cancelled' ){
    	$bt_fetch_table = "hrcon_burden_details";
        $table_id = 'hrcon_jobs_sno';
    }


    $burden_details_sel = "SELECT CONCAT(t1.bt_id,'|',t2.burden_type_name) bt_details, GROUP_CONCAT(CONCAT(t1.bi_id,'^',t3.burden_item_name,'^',t3.burden_value,'^',t3.burden_mode,'^',t3.ratetype,'^',t3.max_earned_amnt,'^',t3.billable_status) SEPARATOR '|') bi_details, t1.ratetype FROM ".$bt_fetch_table." t1 JOIN burden_types t2 ON t2.sno = t1.bt_id JOIN burden_items t3 ON t3.sno = t1.bi_id WHERE t1.".$table_id." = '".$elements[0]."' GROUP BY t1.bt_id";

    $burden_details_res	= mysql_query($burden_details_sel, $db);
    $get_ass_bt_details_cnt = mysql_num_rows($burden_details_res);
    
    while ($row = mysql_fetch_object($burden_details_res)) {
        $burden_details_rec[$row->ratetype]['bt_details'] = $row->bt_details;
        $burden_details_rec[$row->ratetype]['bi_details'] = $row->bi_details;
    }
    
    if(($modfrom=="applicant" || $modfrom=="hiring") && $get_ass_bt_details_cnt == 0)
    {
            $bt_details = str_replace("^^BTSPLIT^^","|",$elements[93]);
            $bi_details = str_replace("^^BTSPLIT^^","|",$elements[94]);
    }
    else
    {
        $bt_details = $burden_details_rec['payrate']['bt_details']; 
	$bi_details = $burden_details_rec['payrate']['bi_details'];
    }

    $bt_exists_flag = 0;
    $existingBurdenOpt = "";
    if($bt_details != "" && $bi_details != "")
    {
            $bt_exists_flag = 1;
            $bt_details_exp = explode("|",$bt_details);
            $bt_sno = $bt_details_exp[0];
            $bt_name = $bt_details_exp[1];

            //forming the burden type str
            $edit_bt_detail_str = $bt_sno."|".$bt_name;
            //Forming the Burden Item Deteails
            $retutnStr = "";
            $totalBurdenVal = 0;
            $flatBurdenVal = "";
            $biDetailsStr = "";
            $bi_details_exp = explode("|",$bi_details);
            foreach($bi_details_exp as $ind_bi_items)
            {
                    $ind_bi_items_exp = explode("^",$ind_bi_items);
                    $suf = "";
                    if($ind_bi_items_exp[3] == 'percentage')
                    {
                            $suf = "%";
                            $totalBurdenVal += $ind_bi_items_exp[2];
                    }
                    else
                    {
                            $flatBurdenVal .= $ind_bi_items_exp[2]."^";
                    }
                    $retutnStr .= $ind_bi_items_exp[1]."-".$ind_bi_items_exp[2].$suf." + ";
                    $biDetailsStr .= $ind_bi_items_exp[0]."^".$ind_bi_items_exp[1]."^".$ind_bi_items_exp[2]."^".$ind_bi_items_exp[3]."^".$ind_bi_items_exp[4]."^".$ind_bi_items_exp[5]."^".$ind_bi_items_exp[6]."|";
            }

            $retutnStr = substr($retutnStr,0,strlen($retutnStr)-2);
            $flatBurdenVal = substr($flatBurdenVal,0,strlen($flatBurdenVal)-1);
            $biDetailsStr = substr($biDetailsStr,0,strlen($biDetailsStr)-1);
            $edit_bi_detail_str = $retutnStr."|".$totalBurdenVal."|".$flatBurdenVal."^^BURDENITEMSPLIT^^".$biDetailsStr;
            $edit_existing_bi_str = $biDetailsStr;
    }
    else if($elements[26] != "" && $elements[26] != 0)
    {
            $existingBurdenOpt = '<option value="old|assignment|'.$elements[26].'" selected>Older Burden</option>';
    }
    
    if(($modfrom=="applicant" || $modfrom=="hiring") && $get_ass_bt_details_cnt == 0)
    {
            $bt_bill_details = str_replace("^^BTSPLIT^^","|",$elements[97]);
            $bi_bill_details = str_replace("^^BTSPLIT^^","|",$elements[98]);
    }
    else
    {
        $bt_bill_details = $burden_details_rec['billrate']['bt_details']; 
	$bi_bill_details = $burden_details_rec['billrate']['bi_details'];
    }
	$btbill_exists_flag = 0;
	$existingBillBurdenOpt	= "";

	if ($bt_bill_details != "" && $bi_bill_details != "") {

		$btbill_exists_flag = 1;
		$bt_bill_details = explode("|", $bt_bill_details);

		$bt_billsno = $bt_bill_details[0];
		$bt_billname = $bt_bill_details[1];

		//forming the burden type str
		$edit_bt_billdetail_str	= $bt_billsno."|".$bt_billname;

		//Forming the Bill Burden Item Details
		$returnStr			= '';
		$billBurdenTotal	= 0;
		$flatBillBurden		= '';
		$billDetailsStr		= '';
		$bi_bill_details	= explode("|", $bi_bill_details);

		foreach ($bi_bill_details as $ind_bill_items) {

                    $suffix = '';
                    $ind_bill_items_exp = explode("^", $ind_bill_items);

                    if ($ind_bill_items_exp[3] == 'percentage') {

                        $suffix = '%';
                        $billBurdenTotal += $ind_bill_items_exp[2];
                    } else {

                        $flatBillBurden .= $ind_bill_items_exp[2] . "^";
                    }

                    $returnStr .= $ind_bill_items_exp[1] . "-" . $ind_bill_items_exp[2] . $suffix . " + ";
                    $billDetailsStr	.= $ind_bill_items_exp[0]."^".$ind_bill_items_exp[1]."^".$ind_bill_items_exp[2]."^".$ind_bill_items_exp[3]."^".$ind_bill_items_exp[4]."^".$ind_bill_items_exp[5]."^".$ind_bill_items_exp[6]."|";
		}

		$returnStr		= substr($returnStr,0,strlen($returnStr)-2);
		$flatBillBurden	= substr($flatBillBurden,0,strlen($flatBillBurden)-1);
		$billDetailsStr	= substr($billDetailsStr,0,strlen($billDetailsStr)-1);

		$edit_bi_bill_detail	= $returnStr."|".$billBurdenTotal."|".$flatBillBurden."^^BURDENITEMSPLIT^^".$billDetailsStr;
		$edit_existing_bill_str	= $billDetailsStr;

	} else if($elements[93] != "" && $elements[93] != 0) {

		$existingBillBurdenOpt	= '<option value="old|assignment|'.$elements[93].'" selected>Older Burden</option>';
	}
    
    
}

$get_bt_list_sql = "SELECT  bt.sno, bt.burden_type_name, bt.ratetype FROM burden_types bt WHERE bt.bt_status = 'Active'";
$get_bt_list_rs = mysql_query($get_bt_list_sql,$db);
$arr_burden_type	= array();

while ($row = mysql_fetch_object($get_bt_list_rs)) {

	$arr_burden_type[$row->sno]['burden_type']	= $row->burden_type_name;
	$arr_burden_type[$row->sno]['rate_type']	= $row->ratetype;
}

//To get burden type id based on location.
$get_bt_id_location_que =   "SELECT burden_type from staffacc_location where sno=".$elements[11];
$get_bt_id_location_rs  =   mysql_query($get_bt_id_location_que,$db);
$bt_id_location_row =   mysql_fetch_row($get_bt_id_location_rs);
$chk_bt =   false;
if($bt_sno  ==  $bt_id_location_row[0])
{
    $chk_bt = true;
}

$customRateIds = "";
if(isset($elements[95]) && $elements[95] != "" && ($modfrom=="applicant" || $modfrom=="hiring"))
{
	$customRateIds = $elements[95];
}

/*
This function used to get the no of position as to load.
*/
function loadItrationNoForAssignment($sessionShift){
	$count=0;
	$target=30;
	$total=0;
	$numbers = $sessionShift;
	foreach($numbers as $key){
	    if($total < $target) {
	        $total = $total+1;
	        $count++;
	    }
	    else
	    {  
	        break;
	    }
	}
	return $count;
}

/*
	Query to get the count of reason codes present in reason_codes table,to check required field or not
*/
$requiredCloseReason = 'N';
$requiredCancelReason = 'N';
$selectCloseReasonCode = "SELECT COUNT(1) AS closeReasonCount FROM reason_codes WHERE `type`='assignclosecode' AND `status`='Active' ";

$selectCancelReasonCode = "SELECT COUNT(1) AS cancelReasonCount FROM reason_codes WHERE `type`='assigncancelcode' AND `status`='Active' ";

$resultClose = mysql_query($selectCloseReasonCode);

$resultCancel = mysql_query($selectCancelReasonCode);
$rowClose = mysql_fetch_assoc($resultClose);
$rowCancel = mysql_fetch_assoc($resultCancel);
if ($rowClose['closeReasonCount'] >0) {
	$requiredCloseReason = 'Y';
}

if ($rowCancel['cancelReasonCount'] >0) {
	$requiredCancelReason = 'Y';
}
/*
	END Reason Code
*/


if ($HRM_HM_SESSIONRN !="") {
	$candrn = $HRM_HM_SESSIONRN;
}else if ($ACC_AS_SESSIONRN !="") {
	$candrn = $ACC_AS_SESSIONRN;
}else if ($candrn =="" && $mode=="newassign") {
	$candrn = strtotime("now");
}else{
	$candrn ='';
}

//Getting Employee ACA Information
$emp_aca_status = 0;
$aca_emp_que = "select sno from aca_emp_data where emp_sno='".$candID."' and eligibility_status='Eligible'";
$aca_emp_res = mysql_query($aca_emp_que,$db);
if(mysql_num_rows($aca_emp_res) > 0){
	$emp_aca_status = 1;
}

?>

<html>
<head>
<title>Assingment</title>
<link href="/BSOS/css/fontawesome.css" rel="stylesheet" type="text/css">
<link href="/BSOS/Home/style_screen.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/educeit.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/grid.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/filter.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/site.css" type=text/css rel=stylesheet>
<link href="/BSOS/css/crm-editscreen.css" type=text/css rel=stylesheet>
<link type="text/css" rel="stylesheet" href="/BSOS/css/crm-summary.css">
<link rel="stylesheet" href="/BSOS/popupmessages/css/popup_message.css" media="screen" type="text/css">
<link href="/BSOS/css/MyProfileCustomStyles.css" rel="stylesheet" type="text/css">
<!-- <link type="text/css" rel="stylesheet" href="/BSOS/css/select2.css"> -->
<link type="text/css" rel="stylesheet" href="/BSOS/css/select2_loc.css">
<link href="/BSOS/css/tooltip.css" rel="stylesheet" type="text/css">
<script>
    var rate_calculator='<?php echo RATE_CALCULATOR;?>';
    var onLoadModeCheck = 'NoMode';
    <?php if($assign =='edit'){?> 
        onLoadModeCheck= 'onload';
     <?php }?>
var MarkupCheck = '';
var pageLoc	= 'assignment';
</script>
<script type="text/javascript" src="/BSOS/popupmessages/scripts/popupMsgArray.js"></script>
<script type="text/javascript" src="/BSOS/popupmessages/scripts/popup-message.js"></script>
<script type="text/javascript" src="/BSOS/Accounting/clients/scripts/validatesupacc.js"></script>
<script language=javascript src=/BSOS/scripts/validateassignment.js></script>
<script language=javascript src="/BSOS/scripts/dynamicElementCreatefun.js"></script>
<script language=javascript src="/BSOS/scripts/tabpane.js"></script>
<script language=javascript src=/BSOS/scripts/place_schedule.js></script>
<script language=javascript src=/BSOS/scripts/schedule.js></script>
<script language="JavaScript" src="/BSOS/scripts/common_ajax.js"></script>
<script language=javascript src="/BSOS/scripts/calMarginMarkuprates.js"></script>
<script language=javascript src="/BSOS/scripts/common.js"></script>
<script language=javascript src="/BSOS/scripts/multiplerates.js"></script>
<script language=javascript src="/BSOS/scripts/jQuery.js"></script>

<script language=javascript src="/BSOS/scripts/accLocations.js"></script>
<script type="text/javascript" src="/BSOS/scripts/eraseSessionVars.js"></script>

<!-- loads modalbox css -->
<link rel="stylesheet" type="text/css" media="all" href="/BSOS/css/shift_schedule/calschdule_modalbox.css" />

<!-- loads jquery & jquery modalbox -->
<!-- <script type="text/javascript" src="/BSOS/scripts/shift_schedule/jquery.min.js"></script> -->
<!-- loads some utilities (not needed for your developments) -->
<link rel="stylesheet" type="text/css" href="/BSOS/css/shift_schedule/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="/BSOS/css/shift_schedule/jquery-ui.structure.css">
<link rel="stylesheet" type="text/css" href="/BSOS/css/shift_schedule/jquery-ui.theme.css">
<link rel="stylesheet" type="text/css" href="/BSOS/css/shift_schedule/schCalendar.css">

<!-- loads jquery ui -->
 <script type="text/javascript" src="/BSOS/scripts/jquery-1.8.3.js"></script> 
<script type="text/javascript" src="/BSOS/scripts/shift_schedule/jquery-ui-1.11.1.js"></script>
<script type="text/javascript" src="/BSOS/scripts/shift_schedule/schCal_timeframe.js"></script>
<!-- <script type="text/javascript" src="/BSOS/scripts/select2.js"></script> -->
<script language=javascript src="/BSOS/scripts/RateCalculator.js"></script>
<script type="text/javascript" src="/BSOS/scripts/shift_schedule/jquery.modalbox.js"></script>

<link type="text/css" rel="stylesheet" href="/BSOS/css/shift_schedule/shiftcolors.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/select2_V_4.0.3.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/gigboardCustom.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/select2_shift.css">
<script type="text/javascript" src="/BSOS/scripts/select2_V4.0.3.js"></script>
<!-- Perdiem Shift Scheduling -->
<link type="text/css" rel="stylesheet" href="/BSOS/css/perdiem_shift_sch/perdiemShifts.css">
<script type="text/javascript" src="/BSOS/scripts/perdiem_shift_sch/PerdiemShiftSch.js"></script>
<script type="text/javascript">
$(window).load(function(){ 
  onLoadModeCheck = 'NoMode';
});

</script>
<script type="text/javascript">
	$(document).ready(function() {
		$("#empname").select2({
  
        placeholder: "Search for all employees",
        minimumInputLength: 0,
        multiple: false,
        closeOnSelect: true,
        ajax: {
            type: "POST",
            url: "/include/timesheet/getSelectorData.php",
            dataType: 'json',
            quietMillis: 500,
            delay: 500,
            data: function (params) {
            	var empids = $('#empname').val();
				var queryParameters = {
				  q: params.term,
				  page: params.page,
				  getAsgnEmployeeSearchVal: params
				}
				return queryParameters;
			},
			processResults: function (data, params) {
			    params.page = params.page || 1;
			    return {
			        results: data.results,
			        /*pagination: {
			            more: (params.page * 10) < data.count_filtered
			        }*/
			    };
			},
			cache: true
		},
            
        language: {
	       	noResults: function(){
	           return "No Employees Found";
	       	},
	       	/*searching: function(){
		        return "<span><i class='fa fa-spin fa-spinner'></i>Fetching Employees...</span>"
		    }*/
	   	},
        escapeMarkup: function (m) {
        	return m; 
        }
    });

});

<?php
if(PAYROLL_PROCESS_BY_MADISON=="MADISON")
	echo "var MADISON_PAYROLL_PROCESS = true;\n";
else
	echo "var MADISON_PAYROLL_PROCESS = false;\n";
?>
$(function() {

	$(".smdaterowclass, .timegridcontainer, .timegrid, .timegridlast").on("change ,click",function(event){
		if ($('#smTimeslotChangedflag') && $('#sm_module').val() == 'assignment') {
			$('#smTimeslotChangedflag').val('Y');
		}

	});
});
function closeAssignmentWindow() {

	eraseSessionVars("assignments", "<?=$apprn?>");
	window.close();
}
function doCopyPage(){

	var v_heigth = 700;
	var v_width  = 1025;
	var top1=(window.screen.availHeight-v_heigth)/2;
	var left1=(window.screen.availWidth-v_width)/2;
	var perameters = window.location.search;
	var copymode = "&copyasign=yes";
	var url = "editassign.php"+perameters+copymode;
	remote= window.location.href = url;
	//remote=window.open(url,"CopyAssignmentPage","width="+v_width+"px,height="+v_heigth+"px,resizable=yes,statusbar=no,menubar=no,scrollbars=yes,left="+left1+"px,top="+top1+"px");
	remote.focus();
}

function studentList(){
	var locid = 0;
	var compid = 0;
	if(document.getElementById('company')){
		var compid = document.getElementById('company').value;
	}else{
		var compid = 0;
	}
	if(document.getElementById('jrt_loc').options){
		var e = document.getElementById("jrt_loc");
		srtlocid = e.options[e.selectedIndex].value;
		aryLocid = srtlocid.split("-");
		var locid = aryLocid[1];		
	}else{
		var locid = 0;
	}
	if(compid != 0 && locid != 0){
		var v_width  = 850;
		var v_heigth = 480;
		var remoteres;
		var top1=(window.screen.availHeight-v_heigth)/2;
		var left1=(window.screen.availWidth-v_width)/2;
		var personids = document.getElementById('studentlistids').value;
		var url = '/BSOS/Accounting/Time_Mngmt/Custom_Timesheet/displayCustomName.php?compid='+compid+'&locid='+locid+'&empid=0&custDisFrom=asign&personlistids='+personids;
		remoteres = window.open(url,"editStudentList","width="+v_width+"px,height="+v_heigth+"px,resizable=yes,statusbar=no,menubar=no,scrollbars=yes,left="+left1+"px,top="+top1+"px");
		remoteres.focus();
	}else{
		alert("Please select a Company and Job Location to select "+document.getElementById('customlablename').value+".");
	}
}
function in_array(needle, haystack){
    var found = 0;
    for (var i=0, len=haystack.length;i<len;i++) {
        if (haystack[i] == needle) return i;
            found++;
    }
    return -1;
}

function removePersonFromAssign(personids){
	var removeid = personids;
	submitedpersonids= Array();
	var removeOption = document.getElementById('personlistdelete').value;
	var personlist = document.getElementById('studentlistids').value;
	var submitedpersonlist = document.getElementById('submitedpersonids').value;
	submitedpersonids = submitedpersonlist.split(',');
	if(removeOption == 'Y'){
		if (confirm(" Do you want to delete " + document.getElementById('personlistname_' + removeid + '').value + "?")) {
			var personlist = document.getElementById('studentlistids').value;
			var personvalue = document.getElementById('personlist_'+removeid).innerHTML = "";
			personlistArry = Array();
			personlistArry = personlist.split(',');
			for (var initi = 0; initi < personlistArry.length; initi++)
		    {
		        if (personlistArry[initi] == personids) {
		            personlistArry.splice(initi, 1);
		        }
		    }
			document.getElementById('studentlistids').value=personlistArry.toString(); 
		}
	}
	if(removeOption == 'N'){
		if(in_array(removeid,submitedpersonids)!= -1){
			//is in array
			alert('You cannot Delete the '+ document.getElementById('personlistname_' + removeid + '').value +' for this Assignment as Time/Expenses have been submitted or transactions have been made.');
		}else{
			if (confirm(" Do you want to delete " + document.getElementById('personlistname_' + removeid + '').value + "?")) {
				var personlist = document.getElementById('studentlistids').value;
				var personvalue = document.getElementById('personlist_'+removeid).innerHTML = "";
				personlistArry = Array();
				personlistArry = personlist.split(',');
				for (var initi = 0; initi < personlistArry.length; initi++)
			    {
			        if (personlistArry[initi] == personids) {
			            personlistArry.splice(initi, 1);
			        }
			    }
				document.getElementById('studentlistids').value=personlistArry.toString(); 
			}
		}
		
	}
	
}
function openCandWindow(sno,str,panel)
{
	var v_width  = 1050;
	var v_heigth = 620;
    	var top1=(window.screen.availHeight-v_heigth)/2;
    	var left1=(window.screen.availWidth-v_width)/2;
	remote=window.open("/BSOS/HRM/Employee_Mngmt/getnewconreg.php?command=emphire&addr=new&rec="+sno,"EmployeeMngt","width="+v_width+"px,height="+v_heigth+"px,statusbar=no,menubar=no,scrollbars=yes,left="+left1+"px,top="+top1+"px,dependent=yes");
	remote.focus();
	
}
</script>
<?php
//Make the date fields disabled only when the shift scheduling display is new
if($schedule_display == 'NEW')
{
?>
<script type="text/javascript">
	
	$(document).ready(function() {
		// Disables Start Date/Due Date/Expected End Date/Hours Section		
		if($('#jo_shiftsch').attr("checked")){
			if($("#smonth").length){
				$('#smonth').attr('disabled', true);
				$('#sday').attr('disabled', true);
				$('#syear').attr('disabled', true);
				$('#josdatecal').hide();
			}			
			if($("#vmonth").length){
				$('#vmonth').attr('disabled', true);
				$('#vday').attr('disabled', true);
				$('#vyear').attr('disabled', true);
				$('#jovdatecal').hide();
			}
			if($("#dmonth").length){
				$('#dmonth').attr('disabled', true);
				$('#dday').attr('disabled', true);
				$('#dyear').attr('disabled', true);
				$('#joddatecal').hide();
			}
			if($("#vtmonth").length){
				$('#vtmonth').attr('disabled', true);
				$('#vtday').attr('disabled', true);
				$('#vtyear').attr('disabled', true);
				$('#jovtdatecal').hide();
			}
			if($("#Hrstype").length){
				$('#Hrstype').attr('disabled', true);
			}
			if($("#notimechk").length){
				$('#notimechk').attr('disabled', true);
			}
			if($("#reason").length){
				$('#reason').attr('disabled', true);
			}
		}
		showhideShiftLegends();
		showSelShift();
	});
</script>
<?php
}
?>
<style>
.accoutingassexport{
	height: 250px !important;
    margin-left: -303px !important;
    margin-top: -195px !important;
    width: 600px !important;
    position: fixed !important;
}
.timehead{ width:4%; display:block; overflow:hidden}
.timeheadPosi{position:absolute; top:0px;text-align:center;  }
.timegrid{ width:2.032%; display:block; overflow:hidden}
.sspadnew table td{ padding:0px !important}
@media screen\0 {	
	/* IE only override */
a.crm-select-link:link{ font-size:12px !important; }
a.edit-list:link{ font-size:12px !important;}
.summaryform-bold-close-title{ font-size:12px !important;}
.center-body { text-align:left !important;}
.crmsummary-jocomp-table td{ font-size:12px ; text-align:left !important;}
.summaryform-nonboldsub-title{ font-size:12px}
#smdatetable{ font-size:12px !important;}
.summaryform-formelement{ text-align:left !important; vertical-align:middle}
.crmsummary-content-title{ text-align:left !important}
.crmsummary-edit-table td{ text-align:left !important}
.summaryform-bold-title{ font-size:12px !important;}
.summaryform-nonboldsub-title{ font-size:12px !important;}
.smdaterowclass td, .timehead{ font-size:12px !important;}
.crmsummary-jocomp-table td.smshiftnamesclass{ font-size:12px !important ; text-align:left !important;}
}
@media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) { 
.timegrid{ width:2.044%; display:block; overflow:hidden}
#readMoreShiftData{float: left !important; margin-left: 50% !important;}
}
#multipleRatesTab table tr td{padding:3px 8px 2px 10px; white-space:nowrap;}
#multipleRatesTab table tr td span select{margin-left:14px }
.panel-table-content-new .summaryform-bold-title { font-size:12px; font-weight:bold;}
.scroll-area{width:795px !important;}
#modal-wrapper{height: 240px; margin-left: -170px; margin-top: -120px; width:800px;}
#modal-glow{ width:100%; position:fixed;}
@media screen and (-webkit-min-device-pixel-ratio:0) { 
    /* Safari only override */
    ::i-block-chrome,.timegrid { width:2.07%; }
	::i-block-chrome,.timehead { width:4%;}   
}
a.crm-select-linkNewBg:hover{color:#3AB0FF}
.custom_defined_main_table td{text-align: left !important; vertical-align: middle;}
.innertbltd-custom table td {vertical-align: middle !important;}
.custom_defined_head_td{width: 173px !important; padding:0px !important;}

.showmeReasonCode{ 
display: none;
}
.showmeReasonCode:hover .showmeReasonDetail{
display : block;
}
#dateSelGridDiv .notestooltiptable td {
    background: #ffffff none repeat scroll 0 0;
    border: 1px solid #d8d8d8;
}
a.tooltip{position: relative;}
a.tooltip span {
    background-color: #ffffff;
    border: 1px solid #a3a3a3;
    border-radius: 3px;
    box-shadow: 0 0 5px 0 #777777;
    opacity: 1;
    white-space: normal;
    left: 10px;
}
a.reassigncodedetail:hover{font-size: 12px;font-weight: normal;}
.reassignbtnNew{ text-align:center;}
.reassignbtnNew input[type="button"]{  background-color: #e9e9e9;
border: 1px solid #979797; border-radius: 4px; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
cursor: pointer;font-family: arial,sans-serif;font-size: 12px;font-weight: bold;  padding: 5px 6px;text-align: center;transition: all 0.5s ease-in-out 0s; margin:5px 0px 0px 15px}
.reassignbtnNew input[type="button"]:hover{background: #01b8f2;
    border: 1px solid #01b8f2;  color: #ffffff; text-decoration: none;}
.crmsummary-jocomp-table .fa-calculator:before{color: #138dc5;font-size:14px;}
.ui-widget-header .ui-icon{ background-position:160px 112px !important}
.ui-widget-content{ border:solid 1px #ccc;}
.modalDialog_transparentDivs{z-index: 99998;}
.ui-dialog #cancelshiftdialog.ui-dialog-content{ padding:5px;}
.ui-dialog #reassignshiftdialog.ui-dialog-content{ padding:5px;}

/*
 Perdiem Shift Scheduling Model Box
*/

.PerdiemAssignmodal-wrapper{height: 500px; margin-left: -170px; margin-top: -120px; width:600px;}
.JoAssignPerdiemModal-wrapper{position: fixed !important; width:450px !important; height: 250px !important; margin-left: -250px !important;}
.JoAssignPerdiemModal-wrapper .scroll-area{width: 440px !important;}

.JoAssignPerdiemEditModal-wrapper{position: fixed !important; width:550px !important; height: 365px !important; margin-left: -275px !important; margin-top:-182px !important}
.JoAssignPerdiemEditModal-wrapper .scroll-area{width: 540px !important;}
.assignPerdiemReAssignModal-wrapper{position: fixed !important; width:440px !important; height: 353px !important; margin-left: -225px !important; margin-top: -175px !important;}
.assignPerdiemReAssignModal-wrapper .scroll-area{width: 440px !important;}

.assignPerdiemHisModal-wrapper{position: fixed !important; width:975px !important; height: 510px !important; margin-left: -485px !important; margin-top: -250px !important; top:50% !important; left:50% !important;}
.assignPerdiemHisModal-wrapper .scroll-area{width: 975px !important;}

body.perdiemnoscroll {
    overflow: hidden;
}
.shiftPagiNation{ height:40px; line-height:40px; margin:0px; clear:both; padding:10px; background:#f1fbff; margin-top:5px;}
.shiftNextPrevious{ line-height:18px;}
.perdiShitSchBg{ margin:4px;}
<!-- perdiem tooltip -->
.tooltipPerdiem {
    display:block;
    position:relative;   
    text-align:left;
}
.tooltipPerdiem .bottom {
    min-width:200px; 
    top:40px;
    left:50%;
    transform:translate(-50%, 0);
    padding:10px;
    color:#444444;
    background-color:#fff;
    font-weight:normal;
    font-size:13px;
    border-radius:8px;
    position:absolute;
    z-index:99999999;
    box-sizing:border-box;
    box-shadow:0 1px 8px rgba(140,137,137,0.5);
    display:none;
	line-height:22px;
	text-align:center;
	
}

.tooltipPerdiem:hover .bottom {
    display:block;	
}

.tooltipPerdiem .bottom i {
    position:absolute;
    bottom:100%;
    left:50%;
    margin-left:-12px;
    width:24px;
    height:12px;
    overflow:hidden;
}

.tooltipPerdiem .bottom i:after {
    content:'';
    position:absolute;
    width:12px;
    height:12px;
    left:50%;
    transform:translate(-50%,50%) rotate(45deg);
    background-color:#fff;
    box-shadow:0 1px 8px rgba(225,225,225,0.5);
}
.toolTipBlkM{ }
.toolTipBlkM .toolTipBlkBdr{ border-bottom:solid 1px #ccc;}
.toolTipBlkM .toolTipBlkBdr:last-child{ border-bottom:solid 0px #ccc;}
.toolTipBlkM .toolTipBlkHed{ font-weight:bold;}
.select2-container, .select2-drop, .select2-search, .select2-search input {
	width: 250px !important;
}

.select2-container--open .select2-dropdown--below {	
	width: 250px !important;
}


</style>
</head>

<body class="center-body" onLoad="hideElements('onload');">
    <input type="hidden" id="payrate_calculate_confirmation" value="<?php if($assign == 'new' || $assign == ''){echo "yes";}?>" />
    <input type="hidden" id="billrate_calculate_confirmation" value="<?php if($assign == 'new' || $assign == ''){echo "yes";}?>" />
    <input type="hidden" id="payrate_calculate_confirmation_window_onblur" value="" />
    <input type="hidden" id="billrate_calculate_confirmation_window_onblur" value="" />
    
<!-- new similar separate fields added for payrate and billrate calculation using calculator-->
<input type="hidden" id="payrate_new_calculate_confirmation_window_onblur" value="" />
<input type="hidden" id="billrate_new_calculate_confirmation_window_onblur" value="" />
<input type="hidden" id="previous_pay_rate" value="" />
<input type="hidden" id="previous_bill_rate" value="" />
<!-- code ends --->
    <input type=hidden name="confirmstate" id="confirmstate" value="edit">
	<input type=hidden name=actstatus>
	<input type=hidden name=savestatus>
	<input type=hidden name=addr value="<?php echo $contact_fetch[2];?>">
	<input type=hidden name=posid id=posid value="<?php echo $posid[0]?>">
	<input type=hidden name=Compsno>
	<input type=hidden name=Compname>
	<input type=hidden name=addnewrow>
	<input type=hidden name=addr value="<?php echo $addr;?>">
	<input type=hidden id="recno" name="recno" value="<?php echo $recno;?>">
	<input type=hidden name=uname value="">
	<input type=hidden name=candstat value="<?php echo $candstat;?>">
	<input type="hidden" name="userid" value="<?php echo $userid; ?>">
	<input type="hidden" name="stat">
	<input type="hidden" name='repcon' value="<?php echo $rep_rows;?>">
	<input type="hidden" name='jobcon' value="<?php echo $jobloc_rows;?>">
	<input type=hidden name=elecount>
	<input type=hidden id="schid" name="schid" value="<?php echo $elements[48];?>">
	<input type=hidden id="moderate_type" name="moderate_type" value="<?php echo $moderate_type;?>">
	<input type=hidden name=fromassign>
	<input type=hidden name=tday value='<?php echo $todate; ?>'>
	<input type="hidden" name='compDisplay'>
	<input type="hidden" name='jobDisplay'>
	<input type="hidden" name='repDisplay'>
	<input type=hidden name=hrcon_sno value='<?php echo $hhid; ?>'>
	<input type=hidden name=empcon_sno value='<?php echo $eeid; ?>'>
	<input type=hidden name=conjob_sno value='<?php echo $conjob_sno; ?>'>
	<input type=hidden name=assgStage>
	<input type=hidden name=venusername>
	<input type="hidden" name='confirmToClose'>
	<input type="hidden" name='currentStatus' value="<?php echo $rsassign;?>">
	<input type="hidden" name="empsno" value="<?php echo $elements[51];?>">
	<input type="hidden" name="tabStatusVal" value="<?php echo $tabStatus;?>">
	<input type="hidden" name="createdate" value="<?php echo $elements[59];?>">
	<input type="hidden" name="newAssCreate" value="<?php echo $newAssCreate;?>">
	<input type="hidden" id="modform" name="modform" value="<?php echo $modfrom;?>">
	<input type="hidden" name="dateplaced" value="<?php echo $elements[70];?>">
	<input type="hidden" name="JobTypeStatusValue" id="JobTypeStatusValue" value="<?php echo $elements[78];?>">
	<input type="hidden" name="payprocessmadison" id="payprocessmadison" value="<?php echo PAYROLL_PROCESS_BY_MADISON;?>">
        <input type="hidden" name="defaultaccupayprocess" id="defaultaccupayprocess" value="<?php echo DEFAULT_AKKUPAY ;?>">
	<input type="hidden" name="mulRatesVal" id="mulRatesVal" value="">
	<input type="hidden" name="roleData" id="roleData" value="<?php echo html_tls_entities($lstDirectInternal,ENT_QUOTES); ?>"> 
	<input type="hidden" name="hdnRoleCount" id="hdnRoleCount" value="">
	<input type="hidden" name="hdnJobType" id="hdnJobType" value="">
	<input type="hidden" name="hdnAssid" id="hdnAssid" value="<?php echo $showAssignid;?>">

	<!-- Hidden fields Payroll processing -->
	<input type="hidden" name="hdnGeoCode" id="hdnGeoCode" value="<?php echo $company_fetch[11]; ?>">
	<input type="hidden" name="hdnState" id="hdnState" value="<?php echo $company_fetch[12]; ?>">
	<input type="hidden" name="hdnCounty" id="hdnCounty" value="<?php echo $company_fetch[13]; ?>">
	<input type="hidden" name="hdnLocal" id="hdnLocal" value="<?php echo $company_fetch[14]; ?>">
	<input type="hidden" name="selectedTaxes" id="selectedTaxes" value="<?php echo $selectedTaxes;?>">
	<input type="hidden" name="hdnTaxAssid" id="hdnTaxAssid" value="<?php echo $showAssignid;?>">
	<!-- End Hidden fields Payroll processing -->
	
	<input type="hidden" name="hdnbralertflag" id="hdnbralertflag" value="<?php echo $br_alert_flag; ?>" />
	<input type="hidden" name="sm_sel_shifts" id="sm_sel_shifts" value="" />
	
	<input type="hidden" name="acccompcrfmstatus" id="acccompcrfmstatus" value="1">
	<input type="hidden" name="acccontactcrfmstatus" id="acccontactcrfmstatus" value="1">
	<input type="hidden" name="customlablename" id="customlablename" value="<?php echo getCustomlabel();?>" >
	<input type="hidden" name="submitedpersonids" id="submitedpersonids" value="">
	<input type="hidden" name="personlistdelete" id="personlistdelete" value="N">
	<input type="hidden" name="studentlistids" id="studentlistids" value="">
	<input type="hidden" id="copyasign" name="copyasign" value="<?php echo $copyasign;?>">
	<input type=hidden name='checked_shift_dates' id='checked_shift_dates' value="" />
	<input type=hidden name='checked_shift_start_end_dates' id='checked_shift_start_end_dates' value="" />
	<input type=hidden name='shift_id' id='shift_id' value="<?php echo $perdiem_shift_id?>" />
	<input id="panel_type" name="panel_type" value="" type="hidden">
	<input type="hidden" name="empcandid" id="empcandid" value="<?=$candID?>">
	<input type="hidden" name="smTimeslotChangedflag" id="smTimeslotChangedflag" value="<?php if($modfrom =='newasgmt' || $copyasign == 'yes'){echo 'Y';}else{echo 'N';} ?>"> 
	<input type="hidden" name="smDeletedTimeslot" id="smDeletedTimeslot" value="">
	<input type="hidden" name="fromGigboard" id="fromGigboard" value="<?php echo $fromGigboard;?>">
	<!--hiddens for Shift Name/ Time -->
	<input type="hidden" name="shift_time_from" id="shift_time_from" value="" />
	<input type="hidden" name="shift_time_to" id="shift_time_to" value="" />
	<input type="hidden" name="shift_sch_status" id="shift_sch_status" value="<?php echo SHIFT_SCHEDULING_ENABLED;?>" />
	<input type="hidden" name="aca_emp_status" id="aca_emp_status" value="<?php echo $emp_aca_status;?>" />

<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
	<td align=center>
	<table width=100% cellspacing=0 cellpadding=0 border=0>
	<tr class="NewGridTopBg">
	<?php
	if(isset($recno)){
		$recassignVal=explode("|",$recno);
		$conjob_sno = $recassignVal[0];
	}
	if($modfrom=="hiring") //from hiring management
	{
		if($assign=="edit")
		{
			$StatusType="update";
			$name=explode("|","fa fa-clone~Update|fa fa-times~Close");
			$link=explode("|","javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		}
		else if($assign=="new")
		{
			$elements[36]="OP"; //To show on project as default assignment type
			$elements[2]=$jtype_name; //To show job type as Temp/Contract by default
			$StatusType="new";

			$name=explode("|","fa-file~Add|fa fa-times~Close");
			$link=explode("|","javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		}
		else
		{
			$name=explode("|","fa fa-thumbs-o-up~Hire|fa fa-hand-o-left~Back|fa fa-hand-o-right~Next");
			$link=explode("|","javascript:doHire(15)|javascript:validate(14,15)|javascript:validate(22,15)");
		}
		$heading="user.gif~Hiring&nbsp;Management";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="empman") //from employee management -- New and Edit
	{
		if($assign!="edit")
		{
			$elements[36]="OP"; //To show on project as default assignment type
			$elements[2]=$jtype_name; //To show job type as Temp/Contract by default
		}

		if($assign=="edit")
		{
			$name=explode("|","fa-arrow-circle-up~Export|fa fa-clone~Update|fa fa-times~Close");
			$StatusType="update";
		}
		else
		{
			$name=explode("|","fa-file~Add|fa fa-times~Close");
			$StatusType="new";
		}

		if($assign=="edit")    
			$link=explode("|","javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		else
			$link=explode("|","javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		$heading="user.gif~Employee&nbsp;Management";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="employee") //from Employee management main page
	{
		if($elements[2]!='0' && $elements[2]!="")
		{
			$name=explode("|","fa-arrow-circle-up~Export|fa fa-clone~Update|fa fa-times~Close");
			$link=explode("|","javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doSPage15('OP','update')|javascript:window.close()");
			$heading="user.gif~Employee&nbsp;Management";
		}
		else
		{
			$name=explode("|","fa fa-clone~Update|fa fa-times~Close");
			$link=explode("|","javascript:doSPage15('OP','update')|javascript:window.close()");
			$heading="user.gif~Employee&nbsp;Management";
		}
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="approve") //Accounting approval
	{
		$name=explode("|","fa-arrow-circle-up~Export|fa-floppy-o~Save|fa fa-check-square-o~Approve|fa-ban~Cancelled|fa fa-times~Close");
		$link=explode("|","javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doApprove(15,this,'approve','save')|javascript:doApprove(15,this,'approve')|javascript:doApprove('cancelled',this,'cancel');|javascript:closeAssignmentWindow()");
		$heading="user.gif~Assignments";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="updateasgmt" && $copyasign !="yes" && $displayCopy =="Yes")  // Accounting update
	{
		$name=explode("|","fa-files-o~Copy|fa-arrow-circle-up~Export|fa fa-clone~Update|fa fa-times~Close");
		$link=explode("|","javascript:doCopyPage();|javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doApprove(15,this,'update')|javascript:closeAssignmentWindow()");
		$heading="user.gif~Assignments";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="updateasgmt" && $copyasign !="yes" && $displayCopy =="No")  // Accounting update
	{
		$name=explode("|","fa-arrow-circle-up~Export|fa fa-clone~Update|fa fa-times~Close");
		$link=explode("|","javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doApprove(15,this,'update')|javascript:closeAssignmentWindow()");
		$heading="user.gif~Assignments";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($copyasign == "yes")  // Copy Assignment 
	{
		$name=explode("|","fa fa-floppy-o~Save|fa fa-times~Close");
		$link=explode("|","javascript:doAsgPage15('OP');|javascript:window.close()");
		$heading="user.gif~Assignments";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="newasgmt") // Accounting new assignments
	{
		$elements[36]="OP"; //To show on project as default assignment type
		$elements[2]=$jtype_name; //To show job type as Temp/Contract by default
		$name=explode("|","fa-file~Add|fa fa-times~Close");
		$link=explode("|","javascript:doAsgPage15('OP');|javascript:window.close()");
		$heading="user.gif~New&nbsp;Assignment";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	?>
	</tr>

	<tr>
		<td><img src=/BSOS/images/white.jpg width=10 heigh=10></td>
	</tr>
<tr>
		<td align=center>
			<table width=98% cellspacing=0 cellpadding=0 border=0>
			<tr>
			  	<td>
					<?php						
						include($app_inc_path."custom/getcustomfields.php");
					?>
				</td>
		  	</tr>
		</table>
		</td>
	</tr>
	<tr>
	<td align=center>
	<div class="form-container">
	<table width=98% cellspacing=0 cellpadding=0 border=0 class="customProfile">
	<tr>
	<td>
	<fieldset>
	<div class="form-back">
	<table width="98%" border="0" class="crmsummary-edit-table">
	<?php
	if($modfrom=="newasgmt" || $copyasign =="yes")
	{
		?>
		<tr>
			<td width="167" class="crmsummary-content-title">Select&nbsp;an&nbsp;Employee&nbsp;</td>
			<td colspan="3"><select name=empname id=empname class="summaryform-formelement" onChange="dispUserEmployeeSno(this);"><option value='' selected>-- Select an Employee --</option><?php echo stripslashes($enames); ?></select><input type="hidden" name="selectedEmpsno" id="selectedEmpsno"/></td>
		</tr>
		<?
	}
	?>
	
	<?php 
	if($modfrom!="newasgmt" && $copyasign !="yes")
	{
		?>
		<tr>
			<td width="167" class="crmsummary-content-title">Candidate&nbsp;Name</td>
			<td colspan=3 class="summaryform-formelement">&nbsp;<?php echo html_tls_specialchars($candidateName,ENT_QUOTES);?></td>
		</tr>
		<?php
	}
	?>

	<tr>
		<td width="167" class="crmsummary-content-title">Created&nbsp;Date</td>
		<td colspan=3 class="summaryform-formelement">&nbsp;<?php echo $disp_cur_date;?></td>
	</tr>

	<?php
	/*For Ticket# 803848 replaced users DB table with emp_list table to use sno as ID for Created By field*/
	$createdBy="";
	/*
		[#835258] FW: Akken - Job Order/Shift feature
		This valiables are used when shift schedule is in disbale mode.
	*/ 
	$shift_id_interval='';
	$shift_starthour_interval='';
	$shift_endhour_interval='';
	if($mode=="newassign" || $modfrom=="newasgmt" || $copyasign == "yes")
	{			
		$create_sql="SELECT users.name, ".getEntityDispName("emp_list.sno","emp_list.name")." FROM users,emp_list WHERE users.username=emp_list.username and users.username = $username";
		$res_create=mysql_query($create_sql,$db);
		$fetch_create=mysql_fetch_row($res_create);
		$createdBy=$fetch_create[1];
		if($copyasign == "yes")
		{
			$copyassign_sql="SELECT hrcon_jobs.shiftid,hrcon_jobs.starthour,hrcon_jobs.endhour FROM hrcon_jobs WHERE hrcon_jobs.sno='".$elements[0]."'";
			$copyassign_res=mysql_query($copyassign_sql,$db);
			$copyassign_row=mysql_fetch_row($copyassign_res);

			$shift_id_interval=$copyassign_row[0];
			$shift_starthour_interval=$copyassign_row[1];
			$shift_endhour_interval=$copyassign_row[2];
		}	 	
	}
	else
	{
		if($modfrom=="applicant" || $modfrom=="hiring"){
			$create_sql="SELECT users.name, ".getEntityDispName("emp_list.sno","emp_list.name").",consultant_jobs.shiftid,consultant_jobs.starthour,consultant_jobs.endhour FROM users,consultant_jobs,emp_list WHERE consultant_jobs.owner=users.username and users.username=emp_list.username and consultant_jobs.sno='".$elements[0]."'";
		}
		else if($modfrom=="empman" || $modfrom=="employee" || $modfrom=="approve" || $modfrom=="updateasgmt"){
			$create_sql="SELECT users.name, ".getEntityDispName("emp_list.sno","emp_list.name").",hrcon_jobs.shiftid,hrcon_jobs.starthour,hrcon_jobs.endhour FROM users,hrcon_jobs,emp_list WHERE hrcon_jobs.owner=users.username and users.username=emp_list.username and hrcon_jobs.sno='".$elements[0]."'";
		}/*else if($modfrom=="approve" || $modfrom=="updateasgmt")
			$create_sql="SELECT users.name, ".getEntityDispName("emp_list.sno","emp_list.name")." FROM users,empcon_jobs,emp_list WHERE empcon_jobs.owner=users.username and users.username=emp_list.username and empcon_jobs.sno='".$elements[0]."'";*/
		$res_create=mysql_query($create_sql,$db);
		$fetch_create=mysql_fetch_row($res_create);
		$createdBy=$fetch_create[1];
		$shift_id_interval=$fetch_create[2];
		$shift_starthour_interval=$fetch_create[3];
		$shift_endhour_interval=$fetch_create[4];

		if(($modfrom=="applicant" || $modfrom=="hiring") && $createdBy=="") //If it is comming from applicant tracking edit assignment page and createdBy  is empty
		{
			$create_sql="SELECT users.name, ".getEntityDispName("emp_list.sno","emp_list.name")." FROM users,emp_list WHERE users.username=emp_list.username and users.username = $username";
			$res_create=mysql_query($create_sql,$db);
			$fetch_create=mysql_fetch_row($res_create);
			$createdBy=$fetch_create[1];
		}
	}
	if (isset($_SESSION["page15ShiftTimeInfo".$HRM_HM_SESSIONRN])) {
		$shiftTimeInfo=explode("|",$_SESSION["page15ShiftTimeInfo".$HRM_HM_SESSIONRN]);
		$shift_id_interval 	= $shiftTimeInfo[2];
		$shift_starthour_interval 	= $shiftTimeInfo[0];
		$shift_endhour_interval 	= $shiftTimeInfo[1];
	}
	?>

	<tr>
		<td width="167" class="crmsummary-content-title">Created&nbsp;By</td>
		<td colspan=3 class="summaryform-formelement">&nbsp;<?php echo stripslashes($createdBy);?></td>
	</tr>

	<?php
	if($mode=="newassign")
	{
		$query2="select contact_manage.city,contact_manage.state,contact_manage.country from contact_manage,hrcon_compen where contact_manage.status !='BP' and hrcon_compen.location=contact_manage.serial_no and hrcon_compen.username='".$username."' and hrcon_compen.ustatus='active'";
	}
	else
	{
		if($elements[60]!="" && $elements[60]!="0" )
			$query2="select contact_manage.city,contact_manage.state,contact_manage.country from contact_manage where contact_manage.status !='BP' and contact_manage.serial_no='".$elements[60]."'";
		else
			$query2="select contact_manage.city,contact_manage.state,contact_manage.country from contact_manage,hrcon_compen where contact_manage.status !='BP' and hrcon_compen.location=contact_manage.serial_no and hrcon_compen.username='".$username."' and hrcon_compen.ustatus='active'";
	}
	$res2=mysql_query($query2,$db);
	$dd2=mysql_fetch_row($res2);

	$officeLoc="";
	if($dd2[0]!="" && $dd2[1]!="" && $dd2[2]!="0")
		$officeLoc=html_tls_specialchars($dd2[0],ENT_QUOTES).", ".html_tls_specialchars($dd2[1],ENT_QUOTES).", ".getCountry($dd2[2]);
	else if($dd2[0]!="" && $dd2[1]=="" && $dd2[2]=="0")
		$officeLoc=html_tls_specialchars($dd2[0],ENT_QUOTES);
	else if($dd2[0]=="" && $dd2[1]!="" && $dd2[2]=="0")
		$officeLoc=html_tls_specialchars($dd2[1],ENT_QUOTES);
	else if($dd2[0]=="" && $dd2[1]=="" && $dd2[2]!="0")
		$officeLoc=getCountry($dd2[2]);			
	else if($dd2[0]!="" && $dd2[1]!="" && $dd2[2]=="0")
		$officeLoc=html_tls_specialchars($dd2[0],ENT_QUOTES).", ".html_tls_specialchars($dd2[1],ENT_QUOTES);
	else if($dd2[0]!="" && $dd2[1]=="" && $dd2[2]!="0")
		$officeLoc=html_tls_specialchars($dd2[0],ENT_QUOTES).", ".getCountry($dd2[2]);
	else if($dd2[0]=="" && $dd2[1]!="" && $dd2[2]!="0")
		$officeLoc=html_tls_specialchars($dd2[1],ENT_QUOTES).", ".getCountry($dd2[2]);	

	//ADDED TO DISPLAY THE BURDEN 0 BY DEFAULT FOR NEW ASSIGNMENT
	$burdenChkFl = 0;
	if($mode == "newassign")
	{
		$burdenChkFl = 1;
	}
	?>

	<tr>
		<td width="167" class="crmsummary-content-title">Office Location</td>
		<td colspan=3 class="summaryform-formelement">&nbsp;<?php echo $officeLoc;?></td>
	</tr>

	<?php
	if($showAssignid!="" && $elements[36]!="" && $copyasign !='yes')
	{
		?>
		<tr>
			<td width="167" class="crmsummary-content-title">Assignment ID</td>
			<td colspan=3 class="summaryform-formelement">&nbsp;<?php echo $showAssignid;?></td>
		</tr>
		<?php 
	}

	if(PAYROLL_PROCESS_BY_MADISON == "MADISON" && ($modfrom == "employee" || $modfrom == "newasgmt" || $modfrom == "approve" || $modfrom == "updateasgmt" || $modfrom == "empman")) 
	{
		?>
		<tr>
			<td width="167" class="crmsummary-content-title">Madison Order ID</td>
			<td><input class="summaryform-formelement" type=text name="madisonorderid" id="madisonorderid" size=25 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[80],ENT_QUOTES);?>"></td>
		</tr>
		<?php
	}
	else 
	{
		echo "<input type=hidden name='madisonorderid' id='madisonorderid' size=25 maxsize=150 maxlength=150 value=''>";
	}
	?>

	<tr>
		<td width="167" class="crmsummary-content-title">Assignment Name<?php if($mandatory_madison == "") { echo $mandatory_synchr_akkupay; } else { echo "&nbsp;".$mandatory_madison; } ?></td>
		<td>
			<?php	
			/* Theraphy Source :: checking Theraphy Source is Enable or not  */
			if(THERAPY_SOURCE_ENABLED=="Y")
			{
			    if(ASSIGNMENT_TITLES == 'TRUE')
			    {	
				
				$snodirect = getManageSno("Direct","jotype");			
				$gettitleTimesheetCount = "SELECT COUNT(1) FROM timesheet_hours WHERE client = '".$company_fetch[2]."' AND assid = '".$showAssignid."' AND username='".$conusername."' AND status NOT IN ('Backup','Deleted')";
				$restitleTimesheetCount = mysql_query($gettitleTimesheetCount,$db);
				$rowtitleTimesheetCount = mysql_fetch_array($restitleTimesheetCount);

				$gettitleExpensCount = "SELECT COUNT(1) FROM expense,par_expense WHERE expense.parid = par_expense.sno AND expense.client = '".$company_fetch[2]."' AND expense.assid = '".$showAssignid."' AND par_expense.username='".$conusername."' AND expense.status NOT IN ('Backup','Deleted')";
				$restitleExpenseCount = mysql_query($gettitleExpensCount,$db);
				$rowtitleExpenseCount = mysql_fetch_array($restitleExpenseCount);					

				$gettitlePlacementCount = "SELECT COUNT(1) FROM hrcon_jobs WHERE jotype='".$snodirect."' AND client='".$company_fetch[2]."' AND username='".$conusername."' AND ustatus IN ('active', 'closed', 'cancel') AND pusername!='$showAssignid'";
				$restitlePlacementCount = mysql_query($gettitlePlacementCount,$db);
				$rowtitlePlacementCount = mysql_fetch_array($restitlePlacementCount);
				
				if(empty($elements[4]))
				{
				    if(($rowtitleTimesheetCount[0] > 0 || $rowtitleExpenseCount[0] > 0 || $rowtitlePlacementCount[0] >0) && $copyasign !='yes')
				     {
					    ?>
						<span id="jotitlespan" class="afontstyle"><?php echo $job_fetch[0];?></span>
						<input class="summaryform-formelement" type="hidden" name=jotitle id="jotitle" size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?> readonly>
						<input name="jobtitleid" id="jobtitleid"  type="hidden"  value="">
						<span id="jotitlelinkspan">
							<a href="javascript:alert('You cannot Select the Title for this Assignment as Time/Expenses have been submitted or transactions have been made.')" class="edit-list">Select Title</a>
						</span>
						<?php if(!empty($jobPosVal)) {?>&nbsp;|&nbsp;
						<a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a>
						<?}?>
				    	<?php
				     }else{
					 ?>
						<span id="jotitlespan" class="afontstyle"><?php echo $job_fetch[0];?></span>
						<input class="summaryform-formelement" type="hidden" name=jotitle id="jotitle" size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?> readonly>
						<input name="jobtitleid" id="jobtitleid"  type="hidden"  value="">
						<span id="jotitlelinkspan">
							<a href="javascript:doSelectJTitles();" class="edit-list">Select Title</a>
						</span>
						<?php if(!empty($jobPosVal)) {?>&nbsp;|&nbsp;
						<a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a>
						<?}?>
						<?php 
				     }
				}
				else
				{    
				     if( ($rowtitleTimesheetCount[0] > 0 || $rowtitleExpenseCount[0] > 0 || $rowtitlePlacementCount[0] >0 ) && $copyasign !='yes')
				     {
					 ?>
						<span id="jotitlespan" class="afontstyle"><?php echo $elements[4]?></span>
						<input class="summaryform-formelement" type="hidden" name=jotitle id="jotitle" size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?> readonly>
						<input name="jobtitleid" id="jobtitleid"  type="hidden"  value="">
						<span id="jotitlelinkspan">
						    <a href="javascript:alert('You cannot change the Title for this Assignment as Time/Expenses have been submitted or transactions have been made.')" class="edit-list">Change Title</a>&nbsp;|&nbsp;<a href="javascript:alert('You cannot Remove the Title for this Assignment as Time/Expenses have been submitted or transactions have been made.')" class="edit-list">Remove Title</a>		
						</span>
						<?php if(!empty($jobPosVal)) {?>
						&nbsp;|&nbsp;
						<a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a>
						<?}?>
						<?php 
					 
				     }
				    else
				    {	   
					?>
						<span id="jotitlespan" class="afontstyle"><?php echo $elements[4]?></span>
						<input class="summaryform-formelement" type="hidden" name=jotitle id="jotitle" size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?> readonly>
						<input name="jobtitleid" id="jobtitleid"  type="hidden"  value="">
						<span id="jotitlelinkspan">
							<a href="javascript:doSelectJTitles();" class="edit-list">Change Title</a>&nbsp;|&nbsp;<a href="javascript:removeTitle()" class="edit-list">Remove Title</a>					
						</span>
						<?php if(!empty($jobPosVal)) {?>
						&nbsp;|&nbsp;
						<a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a>
						<?}?>
					<?php
				     }
				}
			}
		    else
		    {
		    ?>			
			    <input class="summaryform-formelement" type=text name=jotitle size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?>>&nbsp;<?php if($jobPosVal!="" && $jobPosVal!=0) {?><a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a><?}?>
			    <?php
			}
			    
			}else{
			    
			    if(ASSIGNMENT_TITLES == 'TRUE')
			    {
				    if(empty($elements[4]))
				    {
			    	?>

					    <span id="jotitlespan" class="afontstyle"><?php echo $job_fetch[0];?></span>
					    <input class="summaryform-formelement" type="hidden" name=jotitle id="jotitle" size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?> readonly>
					    <input name="jobtitleid" id="jobtitleid"  type="hidden"  value="">
					    <span id="jotitlelinkspan">
						    <a href="javascript:doSelectJTitles();" class="edit-list">Select Title</a>
					    </span>
					    <?php if(!empty($jobPosVal)) {?>&nbsp;|&nbsp;
					    <a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a>
					    <?}?>
				   	 	<?php
				    }
				    else
				    {
			    	?>
					    <span id="jotitlespan" class="afontstyle"><?php echo $elements[4]?></span>
					    <input class="summaryform-formelement" type="hidden" name=jotitle id="jotitle" size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?> readonly>
					    <input name="jobtitleid" id="jobtitleid"  type="hidden"  value="">
					    <span id="jotitlelinkspan">
						    <a href="javascript:doSelectJTitles();" class="edit-list">Change Title</a>&nbsp;|&nbsp;<a href="javascript:removeTitle()" class="edit-list">Remove Title</a>					
					    </span>
					    <?php if(!empty($jobPosVal)) {?>
					    &nbsp;|&nbsp;
					    <a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a>
					    <?}?>
			    	<?php
				    }
			    }
			    else
			    {
			    ?>			
			    	<input class="summaryform-formelement" type=text name=jotitle size=45 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[4],ENT_QUOTES);?>" setName='Assignment Name' <?php echo $spl_Attribute?>>&nbsp;<?php if($jobPosVal!="" && $jobPosVal!=0) {?><a href="javascript:viewSummary('job','<?php echo $jobPosVal?>');" class="edit-list">view job order</a><?}?>
			    <?php
			    }

			}
			?>
		</td>
	</tr>

	<?php
	if($modfrom=="empman" || $modfrom=="employee" || $modfrom=="approve" || $modfrom=="newasgmt" || $modfrom == "updateasgmt" || $copyasign =="yes")
	{
		?>
		<tr id="assignment_status">
			<td width="167" class="crmsummary-content-title">Assignment Status</td>
			<td>
				<select class="summaryform-formelement" name=astatus id=astatus <?php echo $assg_disable;?> onChange="dispCancelNotes(this);">
				<?php 
				if($copyasign == 'yes')
				{
					?>
					<option <?php echo sele("active","active");?> value="active">Active</option>
					<option <?php echo sele("","closed");?> value="closed">Closed</option>
					<?php
				}
				else
				{ 
					if($assignmentStatus=='pending')
					{
						?>
						<option <?php echo sele($elements[55],"active");?> value="active">Needs Approval</option>
						<option <?php echo sele($elements[55],"cancel");?> value="cancel">Cancelled</option>  
						<?php 
					}
					if($assignmentStatus=='approved' || $assignmentStatus=='active' || $assignmentStatus=='editassignment')
					{
						?>
						<option <?php echo sele($elements[55],"active");?> value="active">Active</option>
						<option <?php echo sele($elements[55],"closed");?> value="closed">Closed</option> 
						<option <?php echo sele($elements[55],"cancel");?> value="cancel">Cancelled</option>  
						<?php
					}
					if($assignmentStatus=='newassignment')
					{
						?>
						<option <?php echo sele($elements[55],"active");?> value="active">Active</option>
						<option <?php echo sele($elements[55],"closed");?> value="closed">Closed</option> 
						<?php
					}
				}
				?>
				</select>
			</td>
		</tr>
		<?
	}
	?>

	<input type=hidden name=staff value="OP">

	<tr id="cancelReason" style="display:none">
		<td width="167" class="crmsummary-content-title">Cancel Reason <?php echo ($requiredCancelReason == 'Y' ? "<span class='assignment_cancel_style' >*</span>" : "");  ?></td>
		<td colspan=3 class="summaryform-formelement">&nbsp;<select name="cancel_reason" id="cancel_reason" ><option value="0">Select Reason</option></select>&nbsp;</td>
		<input type='hidden' name='requiredCancelReason' id='requiredCancelReason' value='<?php echo $requiredCancelReason;?>' >
	</tr>
	<tr id="closeReason" style="display:none">
		<td width="167" class="crmsummary-content-title">Close Reason <?php echo ($requiredCloseReason == 'Y' ? '<span class="assignment_cancel_style" >*</span>' : '');  ?></td>
		<td colspan=3 class="summaryform-formelement">&nbsp;<select name="close_reason" id="close_reason" ><option value="0">Select Reason</option></select>&nbsp;</td>
		<input type='hidden' name='requiredCloseReason' id='requiredCloseReason'value='<?php echo $requiredCloseReason;?>' >
	</tr>
	<tr id="cancel_notes" style="display:none">
		<td width="167" class="assignment_cancel_style">Reason for Cancel *</td>
		<td colspan=3 class="summaryform-formelement" valign="top">
		<table width="99%" border="0">
		<tr>
			<td>&nbsp;<textarea name="notes_cancel" cols="35" rows="3" id="notes_cancel"></textarea></td>
			<td class="assignment_cancel_style" valign="middle" align="left" nowrap="nowrap">&nbsp;Please type a reason for cancelling this assignment</td>
		</tr>
		</table>
		</td> 
	</tr>

	</table>
	</div>

	<?php
	if($elements[36]=="")
		$jtype_style="display:none";
	else
		$jtype_style="display:";
	?>

	<div id="jobType1" style="<?php echo $jtype_style;?>">
		<div class="form-back">
		<table width="98%" border="0" class="crmsummary-edit-table">	
		<?php
		if($emp_lstatus == 'INACTIVE') 
			$job_type_disable="disabled";
		else
			$job_type_disable="";
		?>	
		<tr>
			<td width="167" class="crmsummary-content-title">Job Type</td>
			<td>
				<select class="summaryform-formelement" name="jotype" id="jotype" onChange="hideElements('jotypechange');" <?php echo $job_type_disable;?>>
				<option value="">--select--</option>
				<?php
				$jocat="";
				$joindustry="";
				$place_jobtype="";
				$que1="select sno,name,type from manage where type='jotype' or type='jocategory' or type='joindustry' order by name";
				$res1=mysql_query($que1,$db);				
				while($dd1=mysql_fetch_row($res1))
				{
				    
					if($dd1[2]=='jotype')
					{
						print "<option  value='".stripslashes($dd1[0]."|".$dd1[1])."' ".compose_sel($elements[2],$dd1[0])." >".stripslashes($dd1[1])."</option>";
						if($dd1[0]==$elements[2])
						{
							if($dd1[1]=='Temp/Contract' || $dd1[1]=='Internal Temp/Contract' )
							{
								$hire_sal_style="display:none";//td1
								$hire_sal_style1="display:none";//tr
								$hire_sal_style2="display:none";//td2
								$Temp_Block="display:";
								$Temp_None="display:none";
								$Dir_Int="display:none";
								$place_jobtype = $dd1[1];
							}
							else if($dd1[1]=='Direct' || $dd1[1]=='Internal Direct')
							{
								$hide_rate="display:none";
								$hire_sal_style="display:none";//td1
								$hire_sal_style1="display:";//tr
								$hire_sal_style2="display:";//td2
								$Dir_Int="display:none";
								$Dir_Int_pay="display:none";
								$Dir_Int_bill="display:none";
								$place_jobtype = $dd1[1];
							}
							else
							{
								$hire_sal_style="display:";//td1
								$hire_sal_style1="display:";//tr
								$hire_sal_style2="display:";//td2
								$Chng_Block="display:";
								$Chng_None="display:none";
								$place_jobtype = $dd1[1];
							}
						}
					}
					else if($dd1[2]=='jocategory')
					{
						$jocat.="<option  value='".stripslashes($dd1[0])."'".compose_sel($elements[3],$dd1[0])." >".stripslashes($dd1[1])."</option>";
					}
					else if($dd1[2]=='joindustry')
					{
						$joindustry.="<option  value='".stripslashes($dd1[0])."'".compose_sel($elements[91],$dd1[0])." >".stripslashes($dd1[1])."</option>";
					}
				}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td width="167" class="crmsummary-content-title">HRM Department</td>
			<td align=left><?php departmentSelBox('deptassignment', $elements[88], 'summaryform-formelement');?></td>
		</tr>
		</table>
		</div>
	</div>
	
	<?php
	if($elements[2]=="" && $elements[36]=="")
		$jobdet_style="display:none";
	else
		$jobdet_style="display:";
	?>

	<!--***********************************************************************************-->
	
	<?php
		$corp_query	= "SELECT MAX(LENGTH(CONCAT_WS('-', name, description))) FROM corp_code ORDER BY name";
		$corp_result	= mysql_query($corp_query, $db);
		$corp_data	= mysql_fetch_row($corp_result);
		$corp_str	= $corp_data[0];
		$wid_val	= ($corp_str >= 93) ? 'width:550px;' : '';
	?>

	<div id="jobDetails1" style="<?php echo $jobdet_style;?>">
		<div class="form-back">
		<table width="98%" border="0" class="crmsummary-edit-table">
		<tr>
			<td width="167" class="crmsummary-content-title">Industry</td>
			<td align=left>
				<select class="summaryform-formelement" name="joindustryid" id="joindustryid">
				<option value="">--select--</option>
				<?php echo $joindustry; ?>
				</select>
				<a href="javascript:doManage('joindustry','joindustryid');" class="edit-list">edit list</a>
			</td>
		</tr>
		<tr>
			<td width="167" class="crmsummary-content-title">Category</td>
			<td align=left>
				<select class="summaryform-formelement" name="jocat">
				<option value="">--select--</option>
				<?php echo $jocat; ?>
				</select>
				<a href="javascript:doManage('jocategory','jocat');" class="edit-list">edit list</a>
			</td>
		</tr>
		<tr>
			<td width="167" class="crmsummary-content-title">Ref. Code</td>
			<td align=left><input class="summaryform-formelement" type=text name=jorefcode size=40 maxsize=25 maxlength=25 value="<?php echo html_tls_specialchars($elements[5],ENT_QUOTES);?>"></td>
		</tr>
		
		<tr>
			<td width="167" class="crmsummary-content-title">CORP CODE</td>
			<td align=left>
				<select name="corp_code" id="corp_code" class="summaryform-formelement" style="overflow:auto;<? echo $wid_val; ?>">
					<option value=0>--select--</option>
					<?php
					$cc_query	= "SELECT sno, name, description FROM corp_code ORDER BY name";
					$cc_result	= mysql_query($cc_query, $db);

					while ($cc_data = mysql_fetch_row($cc_result)) {

						$cc_text	= $cc_data[1];

						if (isset($cc_data[2]) && !empty($cc_data[2]))
						$cc_text	.= ' - ' . $cc_data[2];

						echo "<option value='$cc_data[0]' ".sele($cc_data[0], $elements[90])." title='".html_tls_specialchars($cc_text, ENT_QUOTES)."'>".html_tls_specialchars($cc_text, ENT_QUOTES).'</option>';
					}
					?>
				</select>
				&nbsp;<a href="javascript:open_corpwin();" class="edit-list">edit list</a>
			</td>
		</tr>
		<tr>
   			<td width="167" class="crmsummary-content-title">Recruiter/Vendor</td>
			<?php
			// below code is for display the consulting vendor of the employee..  
			// Need to display 'Recruiter/Vendor' from vendorsubcon table instead of fetching from hrcon_jobs
			if($candidateName != '' || $new_user != '')
			{
				if($new_user != '')
					$selEmpid = $new_user;
				else
					$selEmpid = $conusername;

				if($selEmpid !=''){
				$getRecruiter = "SELECT vendorsubcon.venid,staffacc_cinfo.cname FROM vendorsubcon,staffacc_cinfo  WHERE staffacc_cinfo.username = vendorsubcon.venid AND vendorsubcon.empid = '".$selEmpid."'";	
				$resRecruiter = mysql_query($getRecruiter,$db);
				$rowRecruiter = mysql_fetch_array($resRecruiter);
				?>
				<td>
					<span id="vencontact-change">&nbsp;<a class="crm-select-link" href="javascript:viewAccCustomers('vendorcompany','<?php echo $rowRecruiter[0];?>');"><?php echo dispTextdb($rowRecruiter[1]);?></a></span>
				</td>
				<?php
				}
				else{
							
			
                $consultant_username = $_SESSION[conusername.$HRM_HM_SESSIONRN];
				$getRecruiter = "SELECT business_name FROM consultant_w4  WHERE username='".$consultant_username."'";
				$resRecruiter = mysql_query($getRecruiter,$db);
				$rowRecruiter = mysql_fetch_array($resRecruiter);			
				
				      $ctocCheck_query="select staffacc_cinfo.sno from staffacc_cinfo 
							where staffacc_cinfo.type IN ('CV','BOTH') 
							AND staffacc_cinfo.cname = '".addslashes(trim($rowRecruiter[0]))."' ";
		
					 $ctocCheck_res=mysql_query($ctocCheck_query,$db);
					 if(mysql_num_rows($ctocCheck_res)==0){ ?>
						  <td>
					        <span id="vencontact-change">&nbsp;<?php echo dispfdb($rowRecruiter[0]);?></span>
				          </td>
				<?php }else{  $fetch = mysql_fetch_array($ctocCheck_res);
			                  $Clienttypeid = $fetch['sno']; 
			           ?>
						<td>
							<span id="vencontact-change">&nbsp;<a class="crm-select-link" href="javascript:viewAccCustomers('vendorcompany','<?php echo $Clienttypeid;?>');"><?php echo dispTextdb($rowRecruiter[0]);?></a></span>
						</td>
			    <?php } ?>
				
				
				<?php
		    	}
		    }
			else
			{
				?>
				<td>
					<span id="vencontact-change">&nbsp;</span>
				</td>
				<?php
			}
			?>				 
			<input type="hidden" name="recven" value="">
		</tr>
		</table>
		</div>

		<div class="form-back">
    	<table width="98%" border="0" class="crmsummary-edit-table">
		<tr>
			<td width="167" class="crmsummary-content-title">Company</td>
			<?php
			$snodirect = getManageSno("Direct","jotype");			
			$getTimesheetCount = "SELECT COUNT(1) FROM timesheet_hours WHERE client = '".$company_fetch[2]."' AND assid = '".$showAssignid."' AND username='".$conusername."' AND status NOT IN ('Backup','Deleted')";
			$resTimesheetCount = mysql_query($getTimesheetCount,$db);
			$rowTimesheetCount = mysql_fetch_array($resTimesheetCount);

			$getExpensCount = "SELECT COUNT(1) FROM expense,par_expense WHERE expense.parid = par_expense.sno AND expense.client = '".$company_fetch[2]."' AND expense.assid = '".$showAssignid."' AND par_expense.username='".$conusername."' AND expense.status NOT IN ('Backup','Deleted')";
			$resExpenseCount = mysql_query($getExpensCount,$db);
			$rowExpenseCount = mysql_fetch_array($resExpenseCount);					

			$getPlacementCount = "SELECT COUNT(1) FROM hrcon_jobs WHERE jotype='".$snodirect."' AND client='".$company_fetch[2]."' AND username='".$conusername."' AND ustatus IN ('active', 'closed', 'cancel') AND pusername!='$showAssignid'";
			$resPlacementCount = mysql_query($getPlacementCount,$db);
			$rowPlacementCount = mysql_fetch_array($resPlacementCount);	

			if($elements[9]!='0' && trim($elements[9])!='' && $comp_num_rows > 0 )
			{
				?>
				<td>
				<span id='company-change'>
				<?php								
				if(($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0) && $copyasign !='yes')
					echo '<a class="crm-select-link" href="javascript:alert(\'You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.\');">'.dispTextdb($company_fetch[0]).'-'.$company_fetch[2].'</a>';
				else
					echo '<a class="crm-select-link" href="javascript:viewAccCustomers(\'jobcompany\',\''.$company_fetch[10].'\',\''.$company_fetch[2].'\');">'.dispTextdb($company_fetch[0]).'-'.$company_fetch[2].'</a>';

				if(($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0) && $copyasign !='yes')
				{							
					?>

					&nbsp;<span class="summaryform-nonboldsub-title">(&nbsp;</span><a href="javascript:alert('You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.')" class="edit-list">change</a>&nbsp;<a href="javascript:alert('You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.')"><i alt='Search' class='fa fa-search'></i></a><span class="summaryform-formelement">&nbsp;|&nbsp;</span><a href="javascript:alert('You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.');" class="edit-list">new</a>&nbsp;<span class="summaryform-nonboldsub-title">)</span>&nbsp;<span><a href="javascript:void(0);" onClick="javascript:alert('You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.');" class='edit-list' title="Remove Company">remove</a></span>

					</span>
					<?php	
				}
				else
				{
					?>

					&nbsp;<span class="summaryform-nonboldsub-title">(&nbsp;</span><a href="javascript:parent_popup('jobcompany')" class="edit-list">change</a>&nbsp;<a href="javascript:parent_popup('jobcompany')"><i alt='Search' class='fa fa-search'></i></a><span class="summaryform-formelement"></span><span class="summaryform-nonboldsub-title">)</span>&nbsp;<span><a href="javascript:RemoveSelectedItem('jobcompany','')" class='edit-list' title="Remove Company">remove</a></span></span>

					<?php
				}
				?>					
				</td>
				<?php
			}
			else
			{
				?>
				<td>		
				<?php
				if(($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0) && $copyasign !='yes')
				{	
					?>
					<span id='company-change'><a class="crm-select-link" href="javascript:alert('You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.');"><strong>select</strong> company</a>&nbsp;<a href="javascript:alert('You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.');"><i alt='Search' class='fa fa-search'></i></a><span class="summaryform-formelement">&nbsp;|&nbsp;</span><a href="javascript:alert('You cannot change the Customer for this Assignment as Time/Expenses have been submitted or transactions have been made.');" class="edit-list"><strong>new</strong> company</a></span>	
					<?php
				}
				else
				{
					?>
					<span id='company-change'><a class="crm-select-link" href="javascript:parent_popup('jobcompany')"><strong>select</strong> company</a>&nbsp;<a href="javascript:parent_popup('jobcompany')"><i alt='Search' class='fa fa-search'></i></a></span>
					<?php
				}
				?>					
				</td>
				<?php
			}
			?>

			<input type="hidden" id="company" name="company" value="<?php echo $elements[9];?>">
			<input type="hidden" id="compname" name="compname" value="<?php echo html_tls_specialchars($company_fetch[0],ENT_QUOTES);?>">
			<input type="hidden" id="comusername" name='comusername' value="<?php echo $company_fetch[10];?>">

			<?php
			if($elements[9]!='0' && trim($elements[9])!='' && $comp_num_rows > 0)
				echo "<input type='hidden' name='comprows' value='1'>";
			else
				echo "<input type='hidden' name='comprows' value='0'>";
			?>
		</tr>
    	</table>
		</div>

		<div class="form-back">
   		<table width="98%" border="0" class="crmsummary-edit-table">
		<tr>
			<td width="167" class="crmsummary-content-title">Contact</td>
			<?php
			 $contact_fetch[1] = stripslashes($contact_fetch[1]);
			if($elements[8]!='0' && trim($elements[8])!='' && $cont_num_rows > 0)
			{
				if($elements[9]!='0' && trim($contact_fetch[2])!='' && $con_sel_rows > 1)
					$selBox="Yes";
				else
					$selBox="No";
				?>
				<td>
				<span id="contact-change">
				<?php
				if(($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0) && $copyasign !='yes')
				{	
					?>
					<span id="conname-change" style="display:none">
					<?php
					echo '<a class="crm-select-link" href="javascript:alert(\'You cannot change the Contact for this Assignment as Time/Expenses have been submitted or transactions have been made.\');">'.html_tls_specialchars($contact_fetch[1],ENT_QUOTES).' - '.$contact_fetch[11].'</a>';
				}
				else
				{
					?>
					<span id="conname-change">
					<?php
					echo '<a class="crm-select-link" href="javascript:viewAccCustomers(\'refcontact\',\''.$contact_fetch[10].'\',\''.$elements[8].'\');">'.html_tls_specialchars($contact_fetch[1],ENT_QUOTES).' - '.$contact_fetch[11].'</a>';								
				}
				?>
				</span>&nbsp;

				<?php
				if($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0 )
				{	
					?>
					<span class="summaryform-nonboldsub-title" style="display:none">(</span><?php if($elements[9]!='0' && trim($contact_fetch[2])!='' && $con_sel_rows > 0){?>&nbsp;<select class="summaryform-formelement" name="jocontact"  onChange="showcontactdata(this.value,'contact','<?php echo $contact_fetch[10];?>')"><option value="">--select--</option>
					<?php
					echo $connames;
					?>
					</select>&nbsp;<span class="summaryform-formelement">&nbsp;|&nbsp;</span><?php }
				}
				else
				{
					?>
					<span class="summaryform-nonboldsub-title">(</span><?php if($elements[9]!='0' && trim($contact_fetch[2])!='' && $con_sel_rows > 0){?>&nbsp;<select class="summaryform-formelement" name="jocontact" onChange="showcontactdata(this.value,'contact','<?php echo $contact_fetch[10];?>')"><option value="">--select--</option>
					<?php
					echo $connames;
					?>
					</select>&nbsp;<span class="summaryform-formelement">&nbsp;|&nbsp;</span><?php }
				}
				?>

				<?php
				if(($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0) && $copyasign !='yes')
				{		
					?>						

					<span id="contactRemove" style="display:none"><a href="javascript:void(0);" onClick="javascript:alert('You cannot change the Contact for this Assignment as Time/Expenses have been submitted or transactions have been made.');" class='edit-list' title="Remove Contact">remove</a></span>

					<a href="javascript:newScreen('contact','refcontact');" class="edit-list"><strong>new</strong> contact</a>
					</span>		
					<?php			
				}
				else
				{
					?>

					<a href="javascript:contact_popup1('refcontact')" class="edit-list">change</a>&nbsp;<a href="javascript:contact_popup1('refcontact')"><i alt='Search' class='fa fa-search'></i></a><span class="summaryform-formelement">&nbsp;|&nbsp;</span><a href="javascript:newScreen('contact','refcontact');" class="edit-list">new</a>&nbsp;<span class="summaryform-nonboldsub-title">)</span>&nbsp;<span id="contactRemove"><a href="javascript:RemoveSelectedItem('refcontact','<?php echo $selBox;?>')" class='edit-list' title="Remove Contact">remove</a></span></span>

					<?php		
				}
				?>
				</td>
				<?php
			}
			else
			{
				?>
				<td>
				<?php
				if(($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0) && $copyasign !='yes')
				{	
					?>					
					<span id="contact-change">						
					<span id="conname-change" style="display:none">					
					<?php
					echo '<a class="crm-select-link" href="javascript:alert(\'You cannot change the Contact for this Assignment as Time/Expenses have been submitted or transactions have been made.\');">'.html_tls_specialchars($contact_fetch[1],ENT_QUOTES).' - '.$contact_fetch[11].'</a>';								
					?>
					</span>
					<span class="summaryform-nonboldsub-title"></span>&nbsp;<select class="summaryform-formelement" name="jocontact" onChange="showcontactdata(this.value,'contact','<?php echo $contact_fetch[10];?>')"><option value="">--select--</option>
					<?php
					echo $connames;
					?>
					</select>&nbsp;<span class="summaryform-formelement">&nbsp;|&nbsp;</span>

					<span id="contactRemove" style="display:none"><a href="javascript:void(0);" onClick="javascript:alert('You cannot change the Contact for this Assignment as Time/Expenses have been submitted or transactions have been made.');" class='edit-list' title="Remove Contact">remove</a></span>

					<a href="javascript:newScreen('contact','refcontact');" class="edit-list"><strong>new</strong> contact</a></span>
					<?php
				}
				else
				{
					?>
					<span id="contact-change"><a class="crm-select-link" href="javascript:contact_popup1('refcontact')"><strong>select</strong> contact</a>&nbsp;<a href="javascript:contact_popup1('refcontact')"><i alt='Search' class='fa fa-search'></i></a><span class="summaryform-formelement">&nbsp;|&nbsp;</span><a href="javascript:newScreen('contact','refcontact');" class="edit-list"><strong>new</strong> contact</a></span>
					<?php
				}
				?>
				</td>
				<?php
			}
			?>
			<input type="hidden" name="contact" value="<?php echo $elements[8];?>">
			<input type="hidden" name="timeExpPlaceRow" id="timeExpPlaceRow" value="<?php echo $rowTimesheetCount[0]."|".$rowExpenseCount[0]."|".$rowPlacementCount[0];?>">				
			<input type="hidden" name="conname" value="<?php echo html_tls_specialchars($contact_fetch[1],ENT_QUOTES);?>">

			<?php
			if($elements[8]!='0' && trim($elements[8])!='' && $cont_num_rows > 0)
				echo "<input type='hidden' name='controws' value='1'>";
			else
				echo "<input type='hidden' name='controws' value='0'>";
			?>
		</tr>
		</table>
		</div>

		<?php
		$jrtcontact=$elements[10];
		$jrt_loc=$elements[11];

		if ($jrtcontact!=0) {

			$que2="SELECT CONCAT_WS( ' ', staffacc_contact.fname, staffacc_contact.mname, staffacc_contact.lname),staffacc_cinfo.sno,staffacc_cinfo.username, staffacc_contact.sno FROM staffacc_contact LEFT JOIN staffacc_cinfo on staffacc_contact.username = staffacc_cinfo.username AND staffacc_cinfo.type IN ('CUST','BOTH') LEFT JOIN staffacc_list ON staffacc_list.username = staffacc_cinfo.username WHERE staffacc_contact.sno ='".$jrtcontact."' AND staffacc_list.status = 'ACTIVE' AND staffacc_contact.acccontact='Y' and staffacc_contact.username!=''";

			$res2=mysql_query($que2,$db);

			$row2=mysql_fetch_row($res2);

			$jrtcont=$row2[0];

			$jrtcompany=$row2[1];

			$jrtcont_stat=$row2[2];

			$jrtcont_id=$row2[3];

		} elseif($jrt_loc>0 && ($jrtcontact==0 || $jrtcontact=="")) {

			$que2="select csno from staffacc_location where sno='".$jrt_loc."' and ltype in ('com','loc')";

			$res2=mysql_query($que2,$db);

			$row2=mysql_fetch_row($res2);

			$jrtcompany=$row2[0];
		}
		$cus_user="select username from staffacc_cinfo where sno='".$jrtcompany."'";
                $cus_user_res=mysql_query($cus_user,$db);
                $cust_user=mysql_fetch_row($cus_user_res);
                $custuser=$cust_user[0];
		?>

		<div class="form-back">
		<table width="99%" border="0" class="crmsummary-edit-table">
		<tr>
			<input type="hidden" name="jrtcompany_sno" id="jrtcompany_sno" value="<?php echo $jrtcompany;?>">
			<input type="hidden" name="jrtcompany_username" id="jrtcompany_username" value="<?php echo $custuser;?>">
			<td width=167 class="crmsummary-content-title">Job Location<?php echo $mandatory_synchr_akkupay; ?></td>
			<td><span id="jrtdisp_comp"><input type="hidden" name="jrt_loc" id="jrt_loc"><a class="crm-select-link" href="javascript:bill_jrt_comp('jrt')">select company</a>&nbsp;</span></span>&nbsp;<span id="jrtcomp_chgid">&nbsp;</span></td>
		</tr>
		</table>
		</div>
        
        <div class="form-back">
		<table width="99%" border="0" class="crmsummary-edit-table">
		<tr>
			<input type="hidden" name="jrtcontact_sno" id="jrtcontact_sno" value="<?php echo $jrtcontact;?>">
			<td width="167" class="crmsummary-content-title">Job Reports To</td>
			<td>
			<?php
			if($jrtcontact=='0' || $jrtcontact=='')
			{
			?>
				<span id="jrtdisp">
				<a class="crm-select-link" href="javascript:bill_jrt_cont('jrt')">select contact</a></span>
				&nbsp;<span id="jrtchgid"><a href="javascript:bill_jrt_cont('jrt')"><i alt='Search' class='fa fa-search'></i></a>

				<!--<span class="summaryform-formelement">&nbsp;|&nbsp;</span><a class="crm-select-link" href="javascript:donew_add('jrt')">new&nbsp;contact</a>-->

				</span>
			<?
			}
			else 
			{
			?>
				<span id="jrtdisp"><a class="crm-select-link" href="javascript:contact_func('<?php echo $jrtcontact;?>','<?php echo $jrtcont_stat;?>','jrt')"><?php echo dispfdb($jrtcont)." - ".$jrtcont_id;?></a></span>
				&nbsp;<span id="jrtchgid">
				<span class=summaryform-formelement>(</span><a class=crm-select-link href=javascript:bill_jrt_cont('jrt')>change </a>
				&nbsp;<a href=javascript:bill_jrt_cont('jrt')><i alt='Search' class='fa fa-search'></i></a>
				<!--<span class=summaryform-formelement>&nbsp;|&nbsp;</span><a class=crm-select-link href=javascript:donew_add('jrt')>new</a>-->
				<span class=summaryform-formelement>&nbsp;|&nbsp;</span><a class="crm-select-link" href="javascript:removeContact('jrt')">remove&nbsp;</a>
				<span class=summaryform-formelement>&nbsp;)&nbsp;</span>
				</span>
			<?
			}
			?>
			</td>
		</tr>
		
		<?php  if(DEFAULT_AKKUPAY == 'Y'){
                    if($modfrom=="hiring"){ 
                    $workSiteQuery = "SELECT sno,locid,type,code,description,status
                                            FROM mphr_codes 
                                            WHERE status='Y' AND type='WS' AND locid='".$compenLocationId."'
                                            ORDER BY code ASC ";
                     
                    $Response = mysql_query($workSiteQuery, $db);
                        while ($Row = mysql_fetch_row($Response)) 
                        {
                             if($Row[3]==$worksitecode){ 
                                                    $workSite_option.='<option  value="'. $Row[3] .'" selected >'. $Row[3] . '</option>';
                                            }else{
                                                    $workSite_option.='<option  value="'. $Row[3] .'" >'. $Row[3] . '</option>';
                                            }
                        }
                         
                         ?>
                          <tr>
                    
                    <td class="crmsummary-content-title">WorkSite Code &nbsp;<?php echo $mandatory_akkupay; ?></td>
                    <td>
				<select class="summaryform-formelement"  name="worksitecode" id="worksitecode" style="width:210px;">
				<option value=""> -- Select -- </option>
				<?php echo $workSite_option;?>
				</select>
				
                    </td>
		</tr>
                     <?php } else {  ?>
                         
                        <tr>
                    
                    <td class="crmsummary-content-title">WorkSite Code &nbsp;<?php echo $mandatory_akkupay; ?></td>
                    <td>
				<select class="summaryform-formelement"  name="worksitecode" id="worksitecode" style="width:210px;">
				<option value=""> -- Select -- </option>
				<?php  $workSiteCode = getworkSiteCodes($worksitecode,$candID);
                                echo $workSiteCode;
				?>
				</select>
				
                    </td>
		</tr>  
                 <?php } } ?>
		
		
		
		</table>
		</div>
		<!-- If Therapy Source is Enable Display the Assigned To => Student List in drop down -->
        <?php 
        if(THERAPY_SOURCE_ENABLED=="Y"){
			if(($rowTimesheetCount[0] > 0 || $rowExpenseCount[0] > 0 || $rowPlacementCount[0] >0) && $copyasign !='yes')
			{
		        ?>
		        <div class="form-back">
					<table width="99%" border="0" class="crmsummary-edit-table">
						<tr>
							<td class="crmsummary-content-title" width="167">Assigned To</td>
							<td align="left">
								&nbsp;<a href="javascript:studentList();" class="edit-list">select <?php echo getCustomlabel(); ?> <i alt="Search" class="fa fa-search"></i></a>
							</td>
						</tr>
					</table>
					<div id="assignmentPersonList">
					</div>
				</div>
		        <?php
		    }else{
		    	?>
		        <div class="form-back">
					<table width="99%" border="0" class="crmsummary-edit-table">
						<tr>
							<td class="crmsummary-content-title" width="167">Assigned To</td>
							<td align="left">
								&nbsp;<a href="javascript:studentList();" class="crm-select-link">select <?php echo getCustomlabel(); ?> <i alt="Search" class="fa fa-search"></i></a> 
							</td>
						</tr>
					</table>
					<div id="assignmentPersonList">
					</div>
				  </div>
		        <?php
		    }    
        }
        ?>
		<div class="form-back">
		<table width="98%" border="0" class="crmsummary-edit-table" id="crm-joborder-scheduleDiv-Table">
		<tr>
			<td width="167" class="crmsummary-content-title">
				<div id="crm-joborder-scheduleDiv-plus1" class="DisplayNone"><a style='text-decoration: none;' href="javascript:classToggle('schedule','plus')"><span class="crmsummary-content-title">Schedule</span></a></div>
				<div id="crm-joborder-scheduleDiv-minus1" ><a style='text-decoration: none;' href="javascript:classToggle('schedule','minus')"><span class="crmsummary-content-title">Schedule</span></a></div>
			</td>
			<td>
				<span id="rightflt" <?php echo $rightflt;?>>
				<span class="summaryform-bold-close-title" id="crm-joborder-scheduleDiv-close" style="width:auto;">
				<a style='text-decoration: none;' onClick="classToggle('schedule','minus')"  href="#crm-joborder-scheduleDiv-plus">close</a>
				</span>
				<span class="summaryform-bold-close-title" id="crm-joborder-scheduleDiv-open" style="display:none;width:auto;">
				<a style='text-decoration: none;' onClick="classToggle('schedule','plus')"  href="#crm-joborder-scheduleDiv-minus"> open</a>				 
				</span>
			 	<div class="form-opcl-btnleftside"><div align="left"></div></div>
				<div id="crm-joborder-scheduleDiv-plus" class='DisplayNone'><a onClick="classToggle('schedule','plus')" class="form-op-txtlnk" href="#crm-joborder-scheduleDiv-minus"><b>+</b></a></div>
				<div id="crm-joborder-scheduleDiv-minus"><a onClick="classToggle('schedule','minus')" class="form-cl-txtlnk" href="#crm-joborder-scheduleDiv-plus"><b>-</b></a></div>
				<div class="form-opcl-btnrightside"><div align="left"></div></div>
				</span>
			</td>
		</tr>
		</table>
		</div>

		<div class="jocomp-back" id="crm-joborder-scheduleDiv" name="crm-joborder-scheduleDiv">
		<table width="98%" border="0" class="crmsummary-jocomp-table">
		<tr id="sched-start-date">
			<td width="150" class="summaryform-bold-title">Start Date<?php if($mandatory_madison == "") { echo $mandatory_synchr_akkupay; } else { echo "&nbsp;".$mandatory_madison; } ?></td>
			<td style="white-space:nowrap;">
				<select id="smonth" name="smonth" class="summaryform-formelement">
				<option value="0">Month</option>
				<option <?php echo compose_sel("1",$sdate[0]);?> value="1">January</option>
				<option <?php echo compose_sel("2",$sdate[0]);?> value="2">February</option>
				<option <?php echo compose_sel("3",$sdate[0]);?> value="3">March</option>
				<option <?php echo compose_sel("4",$sdate[0]);?> value="4">April</option>
				<option <?php echo compose_sel("5",$sdate[0]);?> value="5">May</option>
				<option <?php echo compose_sel("6",$sdate[0]);?> value="6">June</option>
				<option <?php echo compose_sel("7",$sdate[0]);?> value="7">July</option>
				<option <?php echo compose_sel("8",$sdate[0]);?> value="8">August</option>
				<option <?php echo compose_sel("9",$sdate[0]);?> value="9">September</option>
				<option <?php echo compose_sel("10",$sdate[0]);?> value="10">October</option>
				<option <?php echo compose_sel("11",$sdate[0]);?> value="11">November</option>
				<option <?php echo compose_sel("12",$sdate[0]);?> value="12">December</option>
				</select>
				<select id="sday" name="sday" class="summaryform-formelement">
				<option value="0">Day</option>
				<option <?php echo compose_sel("01",$sdate[1]);?> value="01">01</option>
				<option <?php echo compose_sel("02",$sdate[1]);?> value="02">02</option>
				<option <?php echo compose_sel("03",$sdate[1]);?> value="03">03</option>
				<option <?php echo compose_sel("04",$sdate[1]);?> value="04">04</option>
				<option <?php echo compose_sel("05",$sdate[1]);?> value="05">05</option>
				<option <?php echo compose_sel("06",$sdate[1]);?> value="06">06</option>
				<option <?php echo compose_sel("07",$sdate[1]);?> value="07">07</option>
				<option <?php echo compose_sel("08",$sdate[1]);?> value="08">08</option>
				<option <?php echo compose_sel("09",$sdate[1]);?> value="09">09</option>
				<option <?php echo compose_sel("10",$sdate[1]);?> value="10">10</option>
				<option <?php echo compose_sel("11",$sdate[1]);?> value="11">11</option>
				<option <?php echo compose_sel("12",$sdate[1]);?> value="12">12</option>
				<option <?php echo compose_sel("13",$sdate[1]);?> value="13">13</option>
				<option <?php echo compose_sel("14",$sdate[1]);?> value="14">14</option>
				<option <?php echo compose_sel("15",$sdate[1]);?> value="15">15</option>
				<option <?php echo compose_sel("16",$sdate[1]);?> value="16">16</option>
				<option <?php echo compose_sel("17",$sdate[1]);?> value="17">17</option>
				<option <?php echo compose_sel("18",$sdate[1]);?> value="18">18</option>
				<option <?php echo compose_sel("19",$sdate[1]);?> value="19">19</option>
				<option <?php echo compose_sel("20",$sdate[1]);?> value="20">20</option>
				<option <?php echo compose_sel("21",$sdate[1]);?> value="21">21</option>
				<option <?php echo compose_sel("22",$sdate[1]);?> value="22">22</option>
				<option <?php echo compose_sel("23",$sdate[1]);?> value="23">23</option>
				<option <?php echo compose_sel("24",$sdate[1]);?> value="24">24</option>
				<option <?php echo compose_sel("25",$sdate[1]);?> value="25">25</option>
				<option <?php echo compose_sel("26",$sdate[1]);?> value="26">26</option>
				<option <?php echo compose_sel("27",$sdate[1]);?> value="27">27</option>
				<option <?php echo compose_sel("28",$sdate[1]);?> value="28">28</option>
				<option <?php echo compose_sel("29",$sdate[1]);?> value="29">29</option>
				<option <?php echo compose_sel("30",$sdate[1]);?> value="30">30</option>
				<option <?php echo compose_sel("31",$sdate[1]);?> value="31">31</option>
				</select>
				<select id="syear" name="syear" class="summaryform-formelement">
				<OPTION VALUE="0">Year</option>
				<?php
					echo $startyear;
				?>
				</select>
				<span id="josdatecal"><input type="hidden" name="josdatenew" id="josdatenew" value="" /><script language='JavaScript'> new tcal ({'formname':'conreg','controlname':'josdatenew'});</script></span>
			</td>
			<td>&nbsp;</td>
		</tr>

		<tr id="hide-expected-date" style=";<?php echo $hide_rate;?>">
			<td  class="summaryform-bold-title">Expected End Date</td>
			<td>
				<select id="vmonth" name="vmonth" class="summaryform-formelement" id="emonth">
				<option value="0">Month</option>
				<option <?php echo compose_sel("1",$hrdate[1]);?> value="1">January</option>
				<option <?php echo compose_sel("2",$hrdate[1]);?> value="2">February</option>
				<option <?php echo compose_sel("3",$hrdate[1]);?> value="3">March</option>
				<option <?php echo compose_sel("4",$hrdate[1]);?> value="4">April</option>
				<option <?php echo compose_sel("5",$hrdate[1]);?> value="5">May</option>
				<option <?php echo compose_sel("6",$hrdate[1]);?> value="6">June</option>
				<option <?php echo compose_sel("7",$hrdate[1]);?> value="7">July</option>
				<option <?php echo compose_sel("8",$hrdate[1]);?> value="8">August</option>
				<option <?php echo compose_sel("9",$hrdate[1]);?> value="9">September</option>
				<option <?php echo compose_sel("10",$hrdate[1]);?> value="10">October</option>
				<option <?php echo compose_sel("11",$hrdate[1]);?> value="11">November</option>
				<option <?php echo compose_sel("12",$hrdate[1]);?> value="12">December</option>
				</select>
				<select id="vday" name="vday" class="summaryform-formelement">
				<option value="0">Day</option>
				<option <?php echo compose_sel("01",$hrdate[2]);?> value="01">01</option>
				<option <?php echo compose_sel("02",$hrdate[2]);?> value="02">02</option>
				<option <?php echo compose_sel("03",$hrdate[2]);?> value="03">03</option>
				<option <?php echo compose_sel("04",$hrdate[2]);?> value="04">04</option>
				<option <?php echo compose_sel("05",$hrdate[2]);?> value="05">05</option>
				<option <?php echo compose_sel("06",$hrdate[2]);?> value="06">06</option>
				<option <?php echo compose_sel("07",$hrdate[2]);?> value="07">07</option>
				<option <?php echo compose_sel("08",$hrdate[2]);?> value="08">08</option>
				<option <?php echo compose_sel("09",$hrdate[2]);?> value="09">09</option>
				<option <?php echo compose_sel("10",$hrdate[2]);?> value="10">10</option>
				<option <?php echo compose_sel("11",$hrdate[2]);?> value="11">11</option>
				<option <?php echo compose_sel("12",$hrdate[2]);?> value="12">12</option>
				<option <?php echo compose_sel("13",$hrdate[2]);?> value="13">13</option>
				<option <?php echo compose_sel("14",$hrdate[2]);?> value="14">14</option>
				<option <?php echo compose_sel("15",$hrdate[2]);?> value="15">15</option>
				<option <?php echo compose_sel("16",$hrdate[2]);?> value="16">16</option>
				<option <?php echo compose_sel("17",$hrdate[2]);?> value="17">17</option>
				<option <?php echo compose_sel("18",$hrdate[2]);?> value="18">18</option>
				<option <?php echo compose_sel("19",$hrdate[2]);?> value="19">19</option>
				<option <?php echo compose_sel("20",$hrdate[2]);?> value="20">20</option>
				<option <?php echo compose_sel("21",$hrdate[2]);?> value="21">21</option>
				<option <?php echo compose_sel("22",$hrdate[2]);?> value="22">22</option>
				<option <?php echo compose_sel("23",$hrdate[2]);?> value="23">23</option>
				<option <?php echo compose_sel("24",$hrdate[2]);?> value="24">24</option>
				<option <?php echo compose_sel("25",$hrdate[2]);?> value="25">25</option>
				<option <?php echo compose_sel("26",$hrdate[2]);?> value="26">26</option>
				<option <?php echo compose_sel("27",$hrdate[2]);?> value="27">27</option>
				<option <?php echo compose_sel("28",$hrdate[2]);?> value="28">28</option>
				<option <?php echo compose_sel("29",$hrdate[2]);?> value="29">29</option>
				<option <?php echo compose_sel("30",$hrdate[2]);?> value="30">30</option>
				<option <?php echo compose_sel("31",$hrdate[2]);?> value="31">31</option>
				</select>
				<select id="vyear" name="vyear" class="summaryform-formelement">
				<OPTION VALUE="0">Year</option>
				<?php
					echo $eendyear;
				?>
				</select>
				<span id="jovdatecal"><input type="hidden" name="jovdatenew" id="jovdatenew" value="" /><script language='JavaScript'> new tcal ({'formname':'conreg','controlname':'jovdatenew'});</script></span>
			</td>
			<td>&nbsp;</td>
		</tr>

		<tr id="sched-end-date">
			<td  class="summaryform-bold-title">End Date</td>
			<td>
				<select id="dmonth" name="dmonth" class="summaryform-formelement">
				<option value="0">Month</option>
				<option <?php echo compose_sel("1",$edate[0]);?> value="1">January</option>
				<option <?php echo compose_sel("2",$edate[0]);?> value="2">February</option>
				<option <?php echo compose_sel("3",$edate[0]);?> value="3">March</option>
				<option <?php echo compose_sel("4",$edate[0]);?> value="4">April</option>
				<option <?php echo compose_sel("5",$edate[0]);?> value="5">May</option>
				<option <?php echo compose_sel("6",$edate[0]);?> value="6">June</option>
				<option <?php echo compose_sel("7",$edate[0]);?> value="7">July</option>
				<option <?php echo compose_sel("8",$edate[0]);?> value="8">August</option>
				<option <?php echo compose_sel("9",$edate[0]);?> value="9">September</option>
				<option <?php echo compose_sel("10",$edate[0]);?> value="10">October</option>
				<option <?php echo compose_sel("11",$edate[0]);?> value="11">November</option>
				<option <?php echo compose_sel("12",$edate[0]);?> value="12">December</option>
				</select>
				<select id="dday" name="dday" class="summaryform-formelement">
				<option value="0">Day</option>
				<option <?php echo compose_sel("01",$edate[1]);?> value="01">01</option>
				<option <?php echo compose_sel("02",$edate[1]);?> value="02">02</option>
				<option <?php echo compose_sel("03",$edate[1]);?> value="03">03</option>
				<option <?php echo compose_sel("04",$edate[1]);?> value="04">04</option>
				<option <?php echo compose_sel("05",$edate[1]);?> value="05">05</option>
				<option <?php echo compose_sel("06",$edate[1]);?> value="06">06</option>
				<option <?php echo compose_sel("07",$edate[1]);?> value="07">07</option>
				<option <?php echo compose_sel("08",$edate[1]);?> value="08">08</option>
				<option <?php echo compose_sel("09",$edate[1]);?> value="09">09</option>
				<option <?php echo compose_sel("10",$edate[1]);?> value="10">10</option>
				<option <?php echo compose_sel("11",$edate[1]);?> value="11">11</option>
				<option <?php echo compose_sel("12",$edate[1]);?> value="12">12</option>
				<option <?php echo compose_sel("13",$edate[1]);?> value="13">13</option>
				<option <?php echo compose_sel("14",$edate[1]);?> value="14">14</option>
				<option <?php echo compose_sel("15",$edate[1]);?> value="15">15</option>
				<option <?php echo compose_sel("16",$edate[1]);?> value="16">16</option>
				<option <?php echo compose_sel("17",$edate[1]);?> value="17">17</option>
				<option <?php echo compose_sel("18",$edate[1]);?> value="18">18</option>
				<option <?php echo compose_sel("19",$edate[1]);?> value="19">19</option>
				<option <?php echo compose_sel("20",$edate[1]);?> value="20">20</option>
				<option <?php echo compose_sel("21",$edate[1]);?> value="21">21</option>
				<option <?php echo compose_sel("22",$edate[1]);?> value="22">22</option>
				<option <?php echo compose_sel("23",$edate[1]);?> value="23">23</option>
				<option <?php echo compose_sel("24",$edate[1]);?> value="24">24</option>
				<option <?php echo compose_sel("25",$edate[1]);?> value="25">25</option>
				<option <?php echo compose_sel("26",$edate[1]);?> value="26">26</option>
				<option <?php echo compose_sel("27",$edate[1]);?> value="27">27</option>
				<option <?php echo compose_sel("28",$edate[1]);?> value="28">28</option>
				<option <?php echo compose_sel("29",$edate[1]);?> value="29">29</option>
				<option <?php echo compose_sel("30",$edate[1]);?> value="30">30</option>
				<option <?php echo compose_sel("31",$edate[1]);?> value="31">31</option>
				</select>
				<select id="dyear" name="dyear" class="summaryform-formelement">
				<option value="0">Year</option>
				<?php
					echo $endyear;
				?>
				</select>
				<span id="joddatecal"><input type="hidden" name="joddatenew" id="joddatenew" value="" /><script language='JavaScript'> new tcal ({'formname':'conreg','controlname':'joddatenew'});</script></span>
			</td>
			<td style="white-space:nowrap"><span class="summaryform-bold-title">Reason</span>&nbsp;<input class="summaryform-formelement" type=text id="reason" name=reason size=40 maxsize=150 maxlength=150 value="<?php echo html_tls_specialchars($elements[17],ENT_QUOTES);?>"></td>
		</tr>
		<tr id="hide-hire-sal" style=";<?php echo $hire_sal_style1;?>">
			<td class="summaryform-bold-title" style="border-bottom: 0px solid #ddd;">Hired Date</td>
			<td  style="border-bottom: 0px solid #ddd;">
				<select id="vtmonth" name="vtmonth" class="summaryform-formelement">
				<option value="0">Month</option>
				<option <?php echo compose_sel("1",$date[1]);?> value="1">January</option>
				<option <?php echo compose_sel("2",$date[1]);?> value="2">February</option>
				<option <?php echo compose_sel("3",$date[1]);?> value="3">March</option>
				<option <?php echo compose_sel("4",$date[1]);?> value="4">April</option>
				<option <?php echo compose_sel("5",$date[1]);?> value="5">May</option>
				<option <?php echo compose_sel("6",$date[1]);?> value="6">June</option>
				<option <?php echo compose_sel("7",$date[1]);?> value="7">July</option>
				<option <?php echo compose_sel("8",$date[1]);?> value="8">August</option>
				<option <?php echo compose_sel("9",$date[1]);?> value="9">September</option>
				<option <?php echo compose_sel("10",$date[1]);?> value="10">October</option>
				<option <?php echo compose_sel("11",$date[1]);?> value="11">November</option>
				<option <?php echo compose_sel("12",$date[1]);?> value="12">December</option>
				</select>
				<select id="vtday" name="vtday" class="summaryform-formelement">
				<option value="0">Day</option>
				<option <?php echo compose_sel("01",$date[2]);?> value="01">01</option>
				<option <?php echo compose_sel("02",$date[2]);?> value="02">02</option>
				<option <?php echo compose_sel("03",$date[2]);?> value="03">03</option>
				<option <?php echo compose_sel("04",$date[2]);?> value="04">04</option>
				<option <?php echo compose_sel("05",$date[2]);?> value="05">05</option>
				<option <?php echo compose_sel("06",$date[2]);?> value="06">06</option>
				<option <?php echo compose_sel("07",$date[2]);?> value="07">07</option>
				<option <?php echo compose_sel("08",$date[2]);?> value="08">08</option>
				<option <?php echo compose_sel("09",$date[2]);?> value="09">09</option>
				<option <?php echo compose_sel("10",$date[2]);?> value="10">10</option>
				<option <?php echo compose_sel("11",$date[2]);?> value="11">11</option>
				<option <?php echo compose_sel("12",$date[2]);?> value="12">12</option>
				<option <?php echo compose_sel("13",$date[2]);?> value="13">13</option>
				<option <?php echo compose_sel("14",$date[2]);?> value="14">14</option>
				<option <?php echo compose_sel("15",$date[2]);?> value="15">15</option>
				<option <?php echo compose_sel("16",$date[2]);?> value="16">16</option>
				<option <?php echo compose_sel("17",$date[2]);?> value="17">17</option>
				<option <?php echo compose_sel("18",$date[2]);?> value="18">18</option>
				<option <?php echo compose_sel("19",$date[2]);?> value="19">19</option>
				<option <?php echo compose_sel("20",$date[2]);?> value="20">20</option>
				<option <?php echo compose_sel("21",$date[2]);?> value="21">21</option>
				<option <?php echo compose_sel("22",$date[2]);?> value="22">22</option>
				<option <?php echo compose_sel("23",$date[2]);?> value="23">23</option>
				<option <?php echo compose_sel("24",$date[2]);?> value="24">24</option>
				<option <?php echo compose_sel("25",$date[2]);?> value="25">25</option>
				<option <?php echo compose_sel("26",$date[2]);?> value="26">26</option>
				<option <?php echo compose_sel("27",$date[2]);?> value="27">27</option>
				<option <?php echo compose_sel("28",$date[2]);?> value="28">28</option>
				<option <?php echo compose_sel("29",$date[2]);?> value="29">29</option>
				<option <?php echo compose_sel("30",$date[2]);?> value="30">30</option>
				<option <?php echo compose_sel("31",$date[2]);?> value="31">31</option>
				</select>
				<select id="vtyear" name="vtyear" class="summaryform-formelement">
				<OPTION VALUE="0">Year</option>
				<?php
				for($i=date('Y')-10;$i<=date('Y')+20;$i++)
					print "<OPTION ".compose_sel($i,$date[0])." VALUE=".$i.">".$i."</option>";
				?></select>
				<span id="jovtdatecal"><input type="hidden" name="jovtdatenew" id="jovtdatenew" value="" /><script language='JavaScript'> new tcal ({'formname':'conreg','controlname':'jovtdatenew'});</script></span>
			</td>
			<td>&nbsp;</td>
		</tr>
			<tr id="shiftname_time" <?php if(SHIFT_SCHEDULING_ENABLED == 'Y') { echo 'style="display:none"'; } ?> class="shiftnameCls">
                <td  class="summaryform-bold-title">Shift Name/ Time</td>
                <td  colspan="3">
                    <select name="new_shift_name" id="new_shift_name" class="summaryform-formelement" onChange="" style="width:128px !important;">
                    <option value="0|0">Select Shift</option>
                    <?php 
                    	$selShiftsQry = "SELECT sno,shiftname,shiftcolor FROM shift_setup WHERE shiftstatus='active' ORDER BY shiftname ASC";
                    	$selShiftsRes = mysql_query($selShiftsQry,$db);
                    	if(mysql_num_rows($selShiftsRes)>0)
                    	{
		                    	while($selShiftsRow = mysql_fetch_array($selShiftsRes))
		                    	{
		                    		$selected = "";
		                    		if($selShiftsRow['sno'] == $shift_id_interval)
		                    		{
		                    			$selected = "selected=selected";
		                    		}
		                    		?>
		                    		<option value="<?php echo $selShiftsRow['sno'].'|'.$selShiftsRow['shiftname']; ?>" <?php echo $selected;?> title="<?php echo $selShiftsRow['shiftcolor'];?>"><?php echo $selShiftsRow['shiftname'];?></option>
				                    
		                     <?php 
		                     	}
		                }
	                   ?>
                    </select>
                    <select name="shift_start_time" id="shift_start_time" class="summaryform-formelement" onChange="" style="width:100px !important;">
                    <option value="0">Start Time</option>
                    <?php echo display_Shift_Times($shift_starthour_interval); ?>
                    </select>
                    <select name="shift_end_time" id="shift_end_time" class="summaryform-formelement" onChange="" style="width:100px !important;">
                    <option value="0">End Time</option>
                    <?php echo display_Shift_Times($shift_endhour_interval); ?>
                    </select>
                  </td>                      
			</tr>
		<tr id="sced-remove-hours">
			<td class="summaryform-bold-title">Hours</td>
			<td><input name="FullPartTimeRecId" type="hidden" value="">
				<select name="Hrstype" id="Hrstype" class="summaryform-formelement" onChange="checkCustom(this.value)">
				<option  value="fulltime" <?php echo compose_sel("fulltime",$elements[14]);?>>Full Time</option>
				<option  value="parttime" <?php echo compose_sel("parttime",$elements[14]);?>>Part Time</option>
				</select>
			</td>
			<td>&nbsp;</td>
		</tr>
		<!-- OLD SHIFT SCHEDULING START -->
		<?php
		//displaying the old shift schedule based on condition		
		if($schedule_display == 'OLD')
		{
		?>
		
		<tr id='crm-joborder-hourscustom'>
			<td colspan="3" style="padding:0px; line-height:0px;">
			<table border="0" width=100% cellspacing="0" cellpadding="0" id="crm-joborder-Tablehours">
			<tr>
				<td width="150">&nbsp;</td>
				<td width="12%">&nbsp;</td>
				<td width="13%">&nbsp;</td>
				<td width="11%">&nbsp;</td>
				<td width="2%">&nbsp;</td>
			<td width="11%"><span id="crm-joborder-custom_deleteall"><a href="#crm-joborder-custom_deleteall" class="edit-list" onClick="javascript:DelselectSchAll()">delete selected</a></span></td>
				<td width="2%"><input type="checkbox" name="customcheckall" id="customcheckall" value="Y" class="summaryform-formelement" onClick="selectSchAll()"></td>
					<td colspan="2">&nbsp;</td>
			</tr>
			<tr id="defRowday0">
				<td class="summaryform-bold-title">&nbsp;</td>
				<td class="summaryform-bold-title">Sunday</td>
				<td class="summaryform-bold-title">&nbsp;<input type=hidden name="defweekday[0]" id="defweekday[0]" value="Sunday"></td>
				<td>
					<select name="fr_hour0" id="fr_hour0" class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="summaryform-bold-title">To</td>
				<td>
					<select name='to_hour0' id="to_hour0" class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td><input type="checkbox" name="daycheck0" id="daycheck0" value="Y" class="summaryform-formelement" onClick="childSchAll();"></td>
				<td colspan="2" class="summaryform-bold-title"></td>
			</tr>
			<tbody id="JoborderAddTable-Sunday"></tbody>
			<tr id="defRowday1">
				<td>&nbsp;</td>
				<td class="summaryform-bold-title">Monday</td>
				<td class="summaryform-bold-title">&nbsp;<input type=hidden name="defweekday[1]" id="defweekday[1]" value="Monday"></td>
				<td>
					<select name='fr_hour1' id='fr_hour1' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="summaryform-bold-title">To</td>
				<td>
					<select name='to_hour1' id='to_hour1' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td><input type="checkbox" name="daycheck1" id="daycheck1" class="summaryform-formelement" value="Y" onClick="childSchAll();"></td>
				<td colspan="2" class="summaryform-bold-title">&nbsp;</td>
			</tr>
			<tbody id="JoborderAddTable-Monday"></tbody>
			<tr id="defRowday2">
				<td>&nbsp;</td>
				<td  class="summaryform-bold-title">Tuesday</td>
				<td  class="summaryform-bold-title">&nbsp;<input type=hidden name="defweekday[2]" id="defweekday[2]" value="Tuesday"></td>
				<td>
					<select name='fr_hour2' id="fr_hour2" class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="summaryform-bold-title">To</td>
				<td>
					<select name='to_hour2' id='to_hour2' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td><input type="checkbox" name="daycheck2" id="daycheck2" class="summaryform-formelement" value="Y" onClick="childSchAll();"></td>
				<td colspan="2" class="summaryform-bold-title">&nbsp;</td>
			</tr>
			<tbody id="JoborderAddTable-Tuesday"></tbody>
			<tr id="defRowday3">
				<td>&nbsp;</td>
				<td  class="summaryform-bold-title">Wednesday</td>
				<td  class="summaryform-bold-title">&nbsp;<input type=hidden name="defweekday[3]" id="defweekday[3]" value="Wednesday"></td>
				<td>
					<select name='fr_hour3' id="fr_hour3" class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="summaryform-bold-title">To</td>
				<td>
					<select name='to_hour3' id='to_hour3' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td><input type="checkbox" name="daycheck3" id="daycheck3" value="Y" class="summaryform-formelement" onClick="childSchAll();"></td>
				<td colspan="2" class="summaryform-bold-title">&nbsp;</td>
			</tr>
			<tbody id="JoborderAddTable-Wednesday"></tbody>
			<tr id="defRowday4">
				<td>&nbsp;</td>
				<td  class="summaryform-bold-title">Thursday</td>
				<td  class="summaryform-bold-title">&nbsp;<input type=hidden name="defweekday[4]" id="defweekday[4]" value="Thursday"></td>
				<td>
					<select name='fr_hour4' id="fr_hour4" class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="summaryform-bold-title">To</td>
				<td>
					<select name='to_hour4' id='to_hour4' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td><input type="checkbox" name="daycheck4" id="daycheck4" class="summaryform-formelement" value="Y" onClick="childSchAll();"></td>
				<td colspan="2" class="summaryform-bold-title"></td>
			</tr>
			<tbody id="JoborderAddTable-Thursday">&nbsp;</tbody>
			<tr  id="defRowday5">
				<td>&nbsp;</td>
				<td  class="summaryform-bold-title">Friday</td>
				<td  class="summaryform-bold-title">&nbsp;<input type=hidden name="defweekday[5]" id="defweekday[5]" value="Friday"></td>
				<td>
					<select name='fr_hour5' id='fr_hour5' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="summaryform-bold-title">To</td>
				<td>
					<select name='to_hour5' id='to_hour5' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td><input type="checkbox" name="daycheck5" id="daycheck5"  class="summaryform-formelement" value="Y" onClick="childSchAll();"></td>
				<td colspan="2" class="summaryform-bold-title">&nbsp;</td>
			</tr>
			<tbody id="JoborderAddTable-Friday"></tbody>
			<tr  id="defRowday6">
				<td >&nbsp;</td>
				<td  class="summaryform-bold-title">Saturday</td>
				<td  class="summaryform-bold-title">&nbsp;<input type=hidden name="defweekday[6]" id="defweekday[6]" value="Saturday"></td>
				<td >
					<select name='fr_hour6' id="fr_hour6" class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="summaryform-bold-title">To</td>
				<td >
					<select name='to_hour6' id='to_hour6' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td><input type="checkbox" name="daycheck6" id="daycheck6" value="Y" class="summaryform-formelement" onClick="childSchAll();"></td>
				<td colspan="2" class="summaryform-bold-title">&nbsp;</td>
			</tr>
			<tbody id="JoborderAddTable-Saturday"></tbody>
			<tbody id="JoborderAddTable" align="left"></tbody>
			<tr>
				<td  class="crmsummary-jocompmin"></td>
				<td  class="crmsummary-jocompmin" >
					<select name="custaddrow_day" id="custaddrow_day" class="summaryform-formelement">
					<option value=''>---Select---</option>
					<option value='Sunday'>Sunday</option>
					<option value='Monday'>Monday</option>
					<option value='Tuesday'>Tuesday</option>
					<option value='Wednesday'>Wednesday</option>
					<option value='Thursday'>Thursday</option>
					<option value='Friday'>Friday</option>
					<option value='Saturday'>Saturday</option>
					</select>
				</td>
				<td  class="crmsummary-jocompmin" nowrap><input type="text" name='custaddrow_date' id='custaddrow_date' value="" class="summaryform-formelement" size="10" maxlength="10" readonly><script language='JavaScript'> new tcal ({'formname':'conreg','controlname':'custaddrow_date'});</script></td>
				<td class="crmsummary-jocompmin">
					<select name='custaddrowfr_hour' id='custaddrowfr_hour' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td class="crmsummary-jocompmin summaryform-bold-title">To</td>
				<td class="crmsummary-jocompmin">
					<select name='custaddrowto_hour' id='custaddrowto_hour' class="summaryform-formelement">
					<?php echo $DispTimes;?>
					</select>
				</td>
				<td colspan="2" class="crmsummary-jocompmin" nowrap="nowrap"><a  href="#crm-joborder-scheduleDiv-minus" onClick="javascript:ScheduleRowCall()" class="crm-select-link" >Add Row</a></td>
				<td class="summaryform-bold-title crmsummary-jocompmin"></td>
			</tr>
			</table>
			</td>
		</tr>
		
		<?php
		}
		// OLD SHIFT SCHEDULING END
		
		//NEW SHIFT SCHEDULING START
		if($schedule_display == 'NEW')
		{
		
			$previousDate = date("Y-m-d",strtotime("-2 months",strtotime(date("Y-m-d"))));
			$getTFTableName = "";			
			if($modfrom=="empman" || $modfrom=="employee" || $modfrom=="approve" || $modfrom=="updateasgmt")
			{
				$getTFTableName = "hrconjob_sm_timeslots";
				$module ="Assign_$modfrom";	//this "Assign_" string is used to identify that module is assignment
			}
			/*else  if($modfrom=="approve" || $modfrom=="updateasgmt")
			{
				$getTFTableName = "empconjob_sm_timeslots";
				$module ="Assign_$modfrom";
			}*/
			else
			{
				$getTFTableName = "consultantjob_sm_timeslots";
				$module ="Assign_$modfrom";
			}

			if($copyasign =='yes'){

				if($rec_exp[2] == 'closed' || $rec_exp[2] == 'cancelled')
				$getTFTableName = "hrconjob_sm_timeslots";
			}
		?>
		<tr id="sch_calendar" <?php if($schedule_display == 'OLD') { echo ' style="display:none" '; } ?>>
			<td class="summaryform-bold-title" colspan="2" style="padding:0px;">
				<?php
					//if(($module == 'Assign_newasgmt' || $module == 'Assign_hiring') && $mode == 'newassign')
						echo $objSchSchedules->displayShiftScheduleWithAddLink('jo_shiftsch', 'No','assignment',$shift_type);
						$smshiftLegendSnos = $objSchSchedules->findShiftsAssoc($elements[0], $module, 'Assign');
					/*else
						echo $objSchSchedules->displayShiftScheduleWithAddLink('jo_shiftsch', 'NoLinks','assignment');*/
				?>
			</td>
			<td align="right" style="text-align:right !important">
				<?php
				$getRecordsAvail = $objHRMScheduleDetails ->isPastORAllShiftTimingsExists($elements[0], $module);
				if($getRecordsAvail)
				{
					$urlAddress = "/include/shift_schedule/viewalltimeframes.php?refid=$elements[0]&status=viewall&module=$module&showAssignid=$showAssignid&copyasign=$copyasign&dsptitle=".html_tls_specialchars($candidateName,ENT_QUOTES);
					$windowName = "placements_$addr";					
				?>
					<a style="text-decoration:none;cursor:pointer;" id="view_past_schedules">
						<span class="linkrow" onclick="javascript:window.open('<?php echo $urlAddress;?>','<?php echo $windowName; ?>','toolbar=no, scrollbars=No, resizable=No, top=200, left=200, width=850, height=600');">View All/History</span>
					</a>
				<? 
				}
				else { echo "&nbsp;"; }
				?>
			</td>
		</tr>
		<tr <?php if($schedule_display == 'OLD') { echo ' style="display:none" '; } ?>>
			<td colspan="3" class="sspadnew" style="padding:0px;" >
			<?php
				if ($HRM_HM_SESSIONRN !="" && $candrn == "") {
					$candrn = $HRM_HM_SESSIONRN;
				}else if($ACC_AS_SESSIONRN !="" && $candrn == "") {
					$candrn = $ACC_AS_SESSIONRN;
				}else if ($candrn =="" && $mode=="newassign") {
					$candrn = strtotime("now");
				}
			?>
			<input type="hidden" name="sm_module" id="sm_module" value="assignment" />
			<input type="hidden" name="shift_schedule_module" id="shift_schedule_module" value="assignments" >
			<input type="hidden" name="sm_hrcon_shift_sno" id="sm_hrcon_shift_sno" value="<?php echo $smshiftLegendSnos;?>" >
			<input type="hidden" name="candrn" id="candrn" value="<?php echo $candrn;?>" >
			<input type="hidden" name="sm_form_grp_ary_val" id="sm_form_grp_ary_val" value="" >

			<?php
			unset($_SESSION['shiftTotalArrayValues'.$candrn]);
			$_SESSION['shiftTotalArrayValues'.$candrn] = array();
			$getDisplayDatesCount = count($schDatesArray);
			$shift_schedule_module = "assignments";
			$getposid = $elements[0];

			if($getDisplayDatesCount == 0)
			{
				//GETTIGN SM AVAILABILITY TIMNEFRAME DETAILS
				$schDatesArray = array();
				$dateTimeSlotsArray = array();
				$totalTimeSlotDateArray = array();
				$slotGrpCntArray = array();
				$dateSlotGrp = array();
				$notEditableArray = array();
				$sm_form_data_array = array();
				$smtfstr = array();
				$smGrpNumAry = array();
				$shift_node_clm = "";
				if ($getTFTableName == "empconjob_sm_timeslots" || $getTFTableName == "hrconjob_sm_timeslots") {
					$shift_node_clm = ",shiftnotes";
				}
				$get_assgn_tf_sql= "SELECT DATE_FORMAT(shift_date,'%m/%d/%Y'),
											shift_starttime,
											shift_endtime,
											event_no,
											event_group_no,
											shift_status,
											sm_sno,
											no_of_positions
											".$shift_node_clm."
									FROM  ".$getTFTableName."
									WHERE pid = '".$elements[0]."' AND shift_date >= '".$previousDate."'
									ORDER BY shift_date ASC";

				$get_assgn_tf_res	= mysql_query($get_assgn_tf_sql, $db);

				if (mysql_num_rows($get_assgn_tf_res) > 0) 
				{
					while ($row	= mysql_fetch_array($get_assgn_tf_res)) 
					{
						$seldateval	= $row[0];			
					
						//convert into minutes
						$fromTF 	= $objSchSchedules->getMinutesFrmDateTime($row[1]);
						$toTF 		= $objSchSchedules->getMinutesFrmDateTime($row[2]);
						$recNo		= $row[3];
						$slotGrpNo	= $row[4];
						$shiftStatus	= $row[5];
						$shiftNameSno	= $row[6];
						
						$shiftSetupDetails = $objSchSchedules->getShiftNameColorBySno($shiftNameSno);
						list($shiftName,$shiftColor) = explode("|",$shiftSetupDetails);
						
						$shiftPosNum	= $row[7];
						$shiftNumPos = $shiftPosNum;
						$shiftnotes = "";
						if($shift_node_clm !=""){
							$shiftnotes = $row[8];
						}
						$schDatesArray[] = $seldateval."^".$fromTF."^".$toTF."^".$recNo."^".$slotGrpNo."^".$shiftStatus."^".$shiftName."^".$shiftColor."^".$shiftNumPos."^".$shiftNameSno."^".$shiftnotes;	
						//smtfstr += $("#smdatetimeval"+iterno).val() + "^" + $("#smdatetimeminval"+iterno).val() + "^" + $("#smdatetimemaxval"+iterno).val() + "^" + $("#smdatetimerecno"+iterno).val() + "^" + $("#smdatetimegrpno"+iterno).val() + "^" + $("#smshiftstatus"+iterno).val() + "^" + $("#smshiftsno"+iterno).val() + "^" + $("#smshiftnumpos"+iterno).val() + "|";
						$smtfstr[] = $seldateval."^".$fromTF."^".$toTF."^".$recNo."^".$slotGrpNo."^".$shiftStatus."^".$shiftNameSno."^".$shiftNumPos."^".$shiftnotes;
						if ($slotGrpNo !=0) {
							$smGrpNumAry[$slotGrpNo][] = $seldateval."^".$fromTF."^".$toTF."^".$recNo."^".$slotGrpNo."^".$shiftStatus."^".$shiftNameSno."^".$shiftNumPos."^".$shiftnotes;
						}							
						$slotKey = date("Ymd",strtotime($seldateval));
						$smFrameData = $fromTF."^".$toTF."^".$recNo."^".$slotGrpNo."^".$shiftStatus."^".$shiftName."^".$shiftColor."^".$shiftNumPos."^".$shiftNameSno."^".$shiftnotes;
						if (!in_array($smFrameData,$dateTimeSlotsArray[$slotKey])) {

							$dateTimeSlotsArray[$slotKey][] = $fromTF."^".$toTF."^".$recNo."^".$slotGrpNo."^".$shiftStatus."^".$shiftName."^".$shiftColor."^".$shiftNumPos."^".$shiftId."^".$shiftnotes;
						}
						if(isset($ess_asgn_edate) && isset($sm_editable)){
							if((strtotime($seldateval) <= strtotime($ess_asgn_edate) || empty($ess_asgn_edate)) && !$sm_editable) // && $shiftStatus != 'busy')
							{		
								$notEditableArray[] = $slotKey;
							}
						}
						if(!in_array($slotKey, $totalTimeSlotDateArray)){
					        $totalTimeSlotDateArray[] = $slotKey;
					        $seldatevals = date("m/d/Y",strtotime($seldateval));
					        $getcalsel_dates_val .= $seldatevals.",";
							$getschcalendar_dates_val .= $seldatevals."^".$fromTF."^".$toTF."^".$recNo."|";
					    }
						if($slotGrpNo != 0 && $slotGrpNo != "")
						{
							$slotGrpCntArray[$slotGrpNo] = $slotGrpNo;
							$seldatevalStr = date("Ymd",strtotime($seldateval));
							$dateSlotGrp[$seldatevalStr][] = $slotGrpNo;
						}
					}				
				}
		
				$getDisplayDatesCount = count($schDatesArray);
			}
			if (count($schDatesArray) >0) {
				$smCalmodefrom = "EditIndShifts";
			}else{
				$smCalmodefrom = "AddIndShifts";
			}
		

			ksort($smtfstr);
			ksort($schDatesArray);

			unset($_SESSION['editShiftTotalArrayValues'.$candrn]);
			unset($_SESSION['editShiftTotalDateArrayValues'.$candrn]);
			unset($_SESSION['editShiftTotalDateSlotGrpArrayValues'.$candrn]);
			unset($_SESSION['editShiftTotalslotGrpCntArray'.$candrn]);
			unset($_SESSION['editShiftSchDatesTotalArrayValues'.$candrn]);
			unset($_SESSION['sm_form_data_array'.$candrn]);
			unset($_SESSION['sm_form_data_grp_array'.$candrn]);
			unset($_SESSION['smDeletedTimeslot'.$candrn]);
			
			$_SESSION['editShiftTotalArrayValues'.$candrn] = array();
			$_SESSION['editShiftTotalDateArrayValues'.$candrn] = array();
			$_SESSION['editShiftTotalslotGrpCntArray'.$candrn] = array();
			$_SESSION['smDeletedTimeslot'.$candrn] = array();
			$_SESSION['editShiftTotalArrayValues'.$candrn] = $dateTimeSlotsArray;			
			$_SESSION['editShiftTotalDateArrayValues'.$candrn] = $totalTimeSlotDateArray;
			$_SESSION['editShiftTotalDateSlotGrpArrayValues'.$candrn] = $dateSlotGrp;
			$_SESSION['editShiftTotalslotGrpCntArray'.$candrn] = $slotGrpCntArray;
			$_SESSION['editShiftSchDatesTotalArrayValues'.$candrn] = array();
			$_SESSION['editShiftSchDatesTotalArrayValues'.$candrn] = $schDatesArray;
			$_SESSION['sm_form_data_array'.$candrn] = array();
			$_SESSION['sm_form_data_array'.$candrn] = $smtfstr;
			$_SESSION['sm_form_data_grp_array'.$candrn] = array();
			$_SESSION['sm_form_data_grp_array'.$candrn] = $smGrpNumAry;

			$nextItration = loadItrationNoForAssignment($schDatesArray);
			$displayTimeFrameGrid = 'Y';
			$displayNoShowReAssign = 'N'; //No
			if ($modfrom != 'newasgmt' && $modfrom != 'hiring') {
				$displayNoShowReAssign = 'Y'; //Yes
			}
			$defaultColorCode = '#456BDB';
			$busyStatusColor = '#04B431';
			include($app_inc_path."shift_schedule/timeFrameView.php");
			?>
			<script>
			if($("#getcalsel_dates").val() == "")
			{
				$("#dateSelGridDiv").hide();
				$("#shiftschAddEdit").hide();
				$("#jo_shiftsch").prop("checked",false);
			}else{
				$('#shift_type').addClass("disabledDiv");
				//$('#schCalModalBoxLink').addClass("disabledDiv");
			}
			//loadAllHiddenShiftdata('<?php echo $conjob_sno;?>','<?php echo $ivalue;?>','','<?php echo $candrn;?>','<?php echo $dateDisplayArrayCount;?>');
			</script>
			<input type="hidden" name="sm_calmodefrom" id="sm_calmodefrom" value="<?php echo $smCalmodefrom;?>" >
			</td>
		</tr>
		<tr style="height: 10px;"><td colspan="3"></td></tr>

		<tr>
			<td class="summaryform-bold-title" colspan="3" style="padding:0px;">
				<!-- Dont remove this hidden variables -->
				<input type="hidden" name="perdiem_shiftid" id="perdiem_shiftid" value="<?php echo $perdiem_shift_id;?>" >
				<input type="hidden" name="delperdiem_shiftid" id="delperdiem_shiftid" value="" >
				<input type="hidden" name="active_perdiem_shiftid" id="active_perdiem_shiftid" value="<?php echo $perdiem_shift_id;?>" >	
				<div id="perdiemShiftSchedule" style="display:none;" class="perdiShitSchBg">
					<?php 

						if ($shift_type == "perdiem" && isset($elements[0])) 
						{
							// For Save Assignements in Hiring Management as Consultants
								if($pageFrom=='consultant')
								{
									$paginationFrom = "consultant";
									$pagiType 		= "edit";
									$doPagiFromId	= 1;
									$doPagiFor 		= "Next";
									$pagingFrom 	= "consultantPage";
									$hrconJobSno    =  $conjob_sno;
								}
								else
								{
									$paginationFrom = "assignment";
									$pagiType = "edit";
									$doPagiFromId=1;
									$doPagiFor = "Next";
									$pagingFrom ="assignPage";
									$hrconJobSno = $elements[0];
									$pusername = $showAssignid;
								}
							
							unset($_SESSION['editAssignPerdiemShiftPagination'.$candrn]);
							unset($_SESSION['editAssignPerdiemShiftSch'.$candrn]);
							unset($_SESSION['modifiedAssignPerdiemShiftSch'.$candrn]);
							
							// Preparing the Session for 1,35 total 35 days default to dispaly
							include_once($app_inc_path.'perdiem_shift_sch/View/paginationHRMPerdiemShifts.php');
						}else{
							if($modfrom=='hiring')
							{
								$paginationFrom = "assignment";
								$pagiType 		= "new";
								$doPagiFromId	= 1;
								$doPagiFor 		= "Next";
								$pagingFrom 	= "assignPage";
								if(count($_SESSION['newAssignPerdiemShiftPagination'.$candrn])>0)
								{
									// Preparing the Session for 1,35 total 35 days default to dispaly
									include_once($app_inc_path.'perdiem_shift_sch/View/paginationHRMPerdiemShifts.php');
								}
																		
							}
							else
							{
								unset($_SESSION['newAssignPerdiemShiftSch'.$candrn]);
								unset($_SESSION['newAssignPerdiemShiftPagination'.$candrn]);
							}
							
						}
					?>
				</div>
			</td>	
		</tr>
			<?php if ($shift_type == "perdiem" && isset($elements[0])) { ?>

			<script>				
				$("#jo_shiftsch").prop("checked",true);		
				$("#shiftschAddEdit").show();	
				$("#perdiemShiftSchedule").show();
				$('#shift_type').addClass("disabledDiv");
				$('#schCalModalBoxLink').addClass("disabledDiv");
			</script>
			<?php
			}
		}
		//NEW SHIFT SCHEDULING END
		?>		
		</table>
		</div>

		<div class="form-back">
		<table width="98%" border="0" class="crmsummary-edit-table" id="crm-joborder-billinginfoDiv-Table">
		<tr>
			<td width="167" class="crmsummary-content-title">
				<div id="crm-joborder-billinginfoDiv-plus1" class="DisplayNone"><a style='text-decoration: none;' href="javascript:classToggle('billinginfo','plus')"><span class="crmsummary-content-title">Billing Information</span></a></div>
				<div id="crm-joborder-billinginfoDiv-minus1"  ><a style='text-decoration: none;' href="javascript:classToggle('billinginfo','minus')"><span class="crmsummary-content-title">Billing Information</span></a></div>
			</td>
			<td>
				<span id="rightflt" <?php echo $rightflt;?>>
				<span class="summaryform-bold-close-title" id="crm-joborder-billinginfoDiv-close" style="width:auto;">
				<a style='text-decoration: none;' onClick="classToggle('billinginfo','minus')"  href="#crm-joborder-billinginfoDiv-plus">close</a>  
				</span>
				<span class="summaryform-bold-close-title" id="crm-joborder-billinginfoDiv-open" style="display:none;width:auto;">
				<a style='text-decoration: none;' onClick="classToggle('billinginfo','plus')"  href="#crm-joborder-billinginfoDiv-minus">open</a> 
				</span>
				<div class="form-opcl-btnleftside"><div align="left"></div></div>
				<div id="crm-joborder-billinginfoDiv-plus" class='DisplayNone'><a onClick="classToggle('billinginfo','plus')" class="form-op-txtlnk" href="#crm-joborder-billinginfoDiv-minus"><b>+</b></a></div>
				<div id="crm-joborder-billinginfoDiv-minus"><a onClick="classToggle('billinginfo','minus')" class="form-cl-txtlnk" href="#crm-joborder-billinginfoDiv-plus"><b>-</b></a></div>
				<div class="form-opcl-btnrightside"><div align="left"></div></div>
				</span>
			</td>
		</tr>
		</table>
		</div>

		<div class="jocomp-back" id="crm-joborder-billinginfoDiv" name="crm-joborder-billinginfoDiv">
		<span id=tab_billinginfo style="display:">
                    <table width="98%" border="0" class="crmsummary-jocomp-table">
                    <tr>
                        <td height="22" colspan="2"><span class="crmsummary-content-title" id=rate_cond><b>Rates</b></span></td>
                    </tr>
                    </table>
		</span>
                <input type="hidden" name="src_status" value="">

		<!-- billing info tabbed pane starts -->
		<!-- Rate tab start-->

		<span id=rate_avail style="display:">
		<table width="98%" border="0" class="crmsummary-jocomp-table">		
                <?php if(RATE_CALCULATOR=='Y'){ ?>
                    <tr>
                        <td colspan="2" style="padding-bottom:6px;">
                            <span class="billInfoNoteStyle">
                                Note : Pay Rate or Bill Rate is calculated using Margin as the default. To calculate using Markup leave Margin blank.
                            </span>
                        </td>
                    </tr>
                    <?php } ?>
		<tr>
			<td width="14%" class="summaryform-bold-title" nowrap="nowrap">Regular Pay Rate<?php if($mandatory_madison == "") { echo $mandatory_synchr_akkupay; } else { echo "&nbsp;".$mandatory_madison; } ?></td>
			<td>    
                                <?php if(RATE_CALCULATOR=='Y'){ ?>
                                <span id="payrate_calculator" style="float:left;cursor:pointer;display:none;"><i class="fa fa-calculator" onclick="javascript:payrateCalculatorFunc();" aria-hidden="true"></i>&nbsp;&nbsp;</span>
                                <?php } ?>
				<span id="leftflt"><input name="payratetype" id="payratetype"  type="radio" onClick="javascript:calrate()" value="rate" <?php echo $payStat;?> <?php echo sent_check("rate",$payFinal[1]);?> class="summaryform-formelement">
				&nbsp;<span class="summaryform-formelement">Rate</span>&nbsp;&nbsp;
				<?php if($payRateVal == '0.00' || $payRateVal == '0') $payRateVal='';?>
				</span><span id="leftflt"><span id="leftflt">
				
				<!--The hidden variable prevpayratevalue is used for passing the Pay Rate value to function  promptOnChangePayRate()-->
				<input type="hidden" name="prevpayratevalue" id="prevpayratevalue" value="<?php echo html_tls_specialchars($payRateVal,ENT_QUOTES);?>">
				
                                <input name="comm_payrate" type="text" id="comm_payrate" value="<?php echo html_tls_specialchars($payRateVal,ENT_QUOTES);?>" size=10 maxlength="9" class="summaryform-formelement" onfocus="javascript:confirmPayRateAutoCalculatoin('comm_payrate','calculateComPayRates');" <?php if(RATE_CALCULATOR=='N'){ ?> onChange="promptOnChangePayRate();" <?php } ?> onblur="javascript:clearConfirmPayRateAutoCalculatoin();" onkeyup="javascript:calculateComPayRates();" onkeypress="return blockNonNumbers(this, event, true, false);">
				</span></span>
				<span id="leftflt">&nbsp;&nbsp;
                                <!-- UOM:get dynamic ratetypes-->
                                    <select name="payrateper" id="payrateper"   onclick="getPreviousRate(this);" onChange="change_Period('billrate');" class="summaryform-formelement"> 

                                            <?php $assignRatesDisp = getNewRateTypes($payPerVal); 
                                            echo  $assignRatesDisp;
                                            ?>
                                    </select>	
				</span>	
				<span id="leftflt">&nbsp;&nbsp;
				<select name="payratecur" id="payratecur"  onChange="change_PeriodNew('billrate');" class="summaryform-formelement">
				<?php
				displayCurrency($payCurVal);
				?>
				</select>
	</span>
				<span id='reg_pay_Bill_NonBill'>
				<input name="payrateBillOpt" id="payrateBillOpt"  type="radio" value="Y" <?php if($ratesDefaultVal['Regular'][0]!=""){ echo getChk($ratesDefaultVal['Regular'][0],"Y");}else{if($displaytypes[0]['value'] == 'B'){echo 'checked="checked"';}} ?> class="BillableRates"><font class="afontstyle">Billable</font>

				<input name="payrateBillOpt" id="payrateBillOpt"  type="radio" value="N" <?php if($ratesDefaultVal['Regular'][0]!=""){ echo getChk($ratesDefaultVal['Regular'][0],"N");}else{if($displaytypes[0]['value'] == 'NB'){echo 'checked="checked"';}} ?>  class="BillableRates"><font class="afontstyle">Non-Billable</font>
				</span>
			
				
				<div style=" line-height:10px;clear:both">&nbsp;</div>
				<?php if($payFinal[3] == '0.00' || $payFinal[3] == '0')$payFinal[3]='';?>
				<input name="payratetype" id="payratetype"  type="radio" value="open" <?php echo sent_check("open",$payFinal[1]);?>  onClick="javascript:calrate()" class="summaryform-formelement">
				&nbsp;<span class="summaryform-formelement">Open</span>
				<input name="comm_open_payrate" type="text" id="comm_open_payrate" value="<?php echo html_tls_specialchars($payFinal[3],ENT_QUOTES);?>" size=38 class="summaryform-formelement">
			</td>
		</tr>
                <tr id="burden-rate" >
                    <td class="summaryform-bold-title">Pay Burden&nbsp;<?php echo ($payburden_status=='Y')? "<span style='color:red'>*</span>": "" ?></td>
                    <input type="hidden" name='autosetwcc' id="autosetwcc" value="<?php echo $autowcc_status;?>"/>
            		<input type="hidden" name='payburdenstatus' id="payburdenstatus" value="<?php echo $payburden_status;?>"/>
                    <td>
                        <input type="hidden" id="manage_burden_status" value="<?php echo $burden_status;?>">
                        <?php 
                            if($burden_status == 'yes'){
                            	if($autowcc_status == 'Y')
			                	{
			                		$Addfunction = "AutoWCChangeAction(this,'joborder',true);";
			                	}else{
			                		$Addfunction ="";
			                	}
                        ?>
                        <input type="hidden" name="btdefaultchk" id="btdefaultchk" value="0" />
                        <input type="hidden" name="hdnbi_details" id="hdnbi_details" value="<?php echo $edit_existing_bi_str; ?>" />
                        <input type="hidden" name="hdnbt_details" id="hdnbt_details" value="<?php echo $edit_bt_detail_str; ?>" />
                        <input type="hidden" name="edithdn_bt_str" id="edithdn_bt_str" value="<?php echo $edit_bt_detail_str; ?>" />
                        <input type="hidden" name="edithdn_bi_str" id="edithdn_bi_str" value="<?php echo $edit_bi_detail_str; ?>" />
                        <input type="hidden" name="hdnTotalBurdenPerc" id="hdnTotalBurdenPerc" value="0" />
                        <input type="hidden" name="hdnTotalBurdenFlat" id="hdnTotalBurdenFlat" value="0" />
            		<input type="hidden" name="comm_burden" id="comm_burden" value="<?php if(isset($elements[26])){echo $elements[26]; }else { echo 0; } ?>"/>
                        <div class="BTContainer">
                                <div>
                                <select name="burdenType" id="burdenType" <?php if(RATE_CALCULATOR=='Y'){?> onchange="doNoCalculateMarkup(this);BTChangeAction(this,'assignment');<?php echo $Addfunction;?>" <?php } else{ ?> onchange="BTChangeAction(this,'assignment');<?php echo $Addfunction;?>" <?php } ?> >
                                	<?php
                                	if($payburden_status == 'Y')
                                	{
                                	?>
                                	<option value="">--Select Pay Burden--</option>
									<?php
									}
                                       echo $existingBurdenOpt;
                                        foreach ($arr_burden_type as $sno => $burden) {
                                            if ($burden['rate_type'] == 'payrate') {
                                            ?>
                                            <option value="<?php if ($bt_sno==$sno){ echo "existing";} else{ echo $sno;} ?>" <?php if($bt_sno==$sno) echo "selected";  ?>><?php echo $burden['burden_type']; ?></option>
                                            <?php
                                            }
                                        }
                                    ?>
                                </select>
                                </div>
                                <div style="vertical-align:middle;">
                                        <b><span id="burdenItemsStr" class="summaryform-formelement">&nbsp;</span></b>
                                </div>
                        </div>
                        <?php 
                            } else {
                                $chk_bt = false;
                               ?>
                            <div class="BTContainer">
                                <div>
                                    <input type="text" name="comm_burden" id="comm_burden" value="<?php if(isset($elements[26])){echo $elements[26]; }else { echo 0; } ?>" <?php if(RATE_CALCULATOR=='Y'){?> onkeyup="doNoCalculateMarkup(this);calculatebtmargin();" <?php } else{ ?> onkeyup="calculatebtmargin();" <?php } ?>  maxlength="9" size="10" onkeypress="return blockNonNumbers(this, event, true, false);"/>
                                </div>
                                <div>
                                    <b><span class="summaryform-formelement">%</span></b>
                                </div>
                            </div>
                        <?php
                            }
                        ?>
                    </td>
		</tr>
		<tr>
			<td class="summaryform-bold-title">Regular Bill Rate<?=$mandatory_madison;?></td>
			<td>    
                                <?php if(RATE_CALCULATOR=='Y'){ ?>
                                <span id="billrate_calculator" style="float:left;cursor:pointer;display:none;"><i class="fa fa-calculator" onclick="javascript:billrateCalculatorFunc();" aria-hidden="true"></i>&nbsp;&nbsp;</span>
                                <?php } ?>
				<span id="leftflt"><input type="radio" <?php echo sent_check("rate",$payFinal[2]);?> <?php echo $billStat;?> name="billratetype" id="billratetype" value="rate" onClick="javascript:calrate()" class="summaryform-formelement">
				&nbsp;<span class="summaryform-formelement">Rate</span>&nbsp;&nbsp;</span><!--<span class="summaryform-formelement"><span id="leftflt"><input name="billratevall1" type="text" id="billratevall1" value="<?php echo html_tls_specialchars($billRateVal,ENT_QUOTES);?>" size=10 onBlur="javascript:cal('add','br')">-->
				<?php if($billRateVal == '0.00' || $billRateVal == '0')$billRateVal='';?>
				<span id="leftflt">
				<!--The hidden variable prevbillratevalue is used for passing the Bill Rate value to function  promptOnChangeBillRate()-->
				<input type="hidden" name="prevbillratevalue" id="prevbillratevalue" value="<?php echo html_tls_specialchars($billRateVal,ENT_QUOTES);?>">
                                <input name="comm_billrate" type="text" id="comm_billrate" value="<?php echo html_tls_specialchars($billRateVal,ENT_QUOTES);?>" size=10 maxlength="9" class="summaryform-formelement" onfocus="javascript:confirmBillRateAutoCalculatoin('comm_billrate');" <?php if(RATE_CALCULATOR=='N'){ ?> onChange="promptOnChangeBillRate();" <?php } ?> onblur="clearConfirmBillRateAutoCalculatoin();" onkeyup="javascript:calculateComBillRates();" onkeypress="return blockNonNumbers(this, event, true, false);">
				</span><span id="leftflt">&nbsp;&nbsp;
                                <!-- UOM:get dynamic ratetypes-->
                                    <select name="billrateper" id="billrateper" onclick="getPreviousRate(this);"  onChange="change_Period('payrate');"  class="summaryform-formelement">
                                        <?php   $assignRatesDisp = getNewRateTypes($billPerVal); 
                                                echo  $assignRatesDisp;
                                        ?>
                                    </select>	
				</span>	
				<span id="leftflt">&nbsp;&nbsp;
				<select name="billratecur" id="billratecur" onChange="change_PeriodNew('payrate');" class="summaryform-formelement">
				<?php
				displayCurrency($billCurVal);
				?>
				</select></span>
				<span id='reg_bill_Tax_NonTax'>
				<input name="billrateTaxOpt" id="billrateTaxOpt"  type="radio" value="Y" <?php if($ratesDefaultVal['Regular'][1]!=""){ echo getChk($ratesDefaultVal['Regular'][1],"Y");}else{if($displaytypes[1]['value'] == 'T'){echo 'checked="checked"';}} ?> ><font class="afontstyle">Taxable</font>

				<input name="billrateTaxOpt" id="billrateTaxOpt"  type="radio" value="N" <?php if($ratesDefaultVal['Regular'][1]!=""){ echo getChk($ratesDefaultVal['Regular'][1],"N");}else{if($displaytypes[1]['value'] == 'NT'){echo 'checked="checked"';}} ?> ><font class="afontstyle">Non-Taxable</font>
				</span>
				<div style=" line-height:10px;clear:both">&nbsp;</div>
				<?php if($payFinal[4] == '0.00' || $payFinal[4] == '0')$payFinal[4]='';?>
				<input name="billratetype" id="billratetype" type="radio" onClick="javascript:calrate()" value="open" <?php echo sent_check("open",$payFinal[2]);?> class="summaryform-formelement">
				&nbsp;<span class="summaryform-formelement">Open</span>&nbsp;<input name="comm_open_billrate" type="text" id="comm_open_billrate" value="<?php echo html_tls_specialchars($payFinal[4],ENT_QUOTES);?>" size=38 class="summaryform-formelement">
			</td>
		</tr>
		<tr id="burden-rate" >
                    <td class="summaryform-bold-title">Bill Burden&nbsp;<?php echo ($billburden_status=='Y')? "<span style='color:red'>*</span>": "" ?></td>
                    <input type="hidden" name='billburdenstatus' id="billburdenstatus" value="<?php echo $billburden_status;?>"/>
                    <td>
                        <?php 
                            if($burden_status == 'yes'){
                        ?>
                        <input type="hidden" name="bill_btdefaultchk" id="bill_btdefaultchk" value="0" />
                        <input type="hidden" name="bill_hdnbi_details" id="bill_hdnbi_details" value="<?php echo $edit_existing_bill_str; ?>" />
                        <input type="hidden" name="bill_hdnbt_details" id="bill_hdnbt_details" value="<?php echo $edit_bt_billdetail_str; ?>" />
                        <input type="hidden" name="bill_edithdn_bt_str" id="bill_edithdn_bt_str" value="<?php echo $edit_bt_billdetail_str; ?>" />
                        <input type="hidden" name="bill_edithdn_bi_str" id="bill_edithdn_bi_str" value="<?php echo $edit_bi_bill_detail; ?>" />
                        <input type="hidden" name="bill_hdnTotalBurdenPerc" id="bill_hdnTotalBurdenPerc" value="0" />
                        <input type="hidden" name="bill_hdnTotalBurdenFlat" id="bill_hdnTotalBurdenFlat" value="0" />
                        <input type="hidden" name="comm_bill_burden" id="comm_bill_burden" value="<?php if(isset($elements[97])) { if(isset($elements[92])){echo $elements[92]; }else { echo 0; } }else{ if(isset($elements[93])){echo $elements[93]; }else { echo 0; } } ?>" />
                        <div class="BTContainer">
                            <div>                        
                                <select name="bill_burdenType" id="bill_burdenType" <?php if(RATE_CALCULATOR=='Y'){?> onchange="doNoCalculateMarkup(this);BillBTChangeAction(this,'assignment');"<?php } else{ ?> onchange="BillBTChangeAction(this,'assignment');" <?php } ?> >
                                	<?php
                                	if($billburden_status == 'Y')
                                	{
                                	?>
                                	<option value="">--Select Bill Burden--</option>
									<?php
									}
                                       echo $existingBillBurdenOpt;
                                        foreach ($arr_burden_type as $sno => $burden) {
                                            if ($burden['rate_type'] == 'billrate') {
                                            ?>
                                            <option value="<?php if ($bt_billsno==$sno){ echo "existing";} else{ echo $sno;} ?>" <?php if($bt_billsno==$sno) echo "selected";  ?>><?php echo $burden['burden_type']; ?></option>
                                            <?php
                                            }
                                        }
                                    ?>
                                </select>
                            </div>
                            <div style="vertical-align:middle;">
                                <b><span id="bill_burdenItemsStr" class="summaryform-formelement">&nbsp;</span></b>
                            </div>				
                        </div>
                        <?php 
                            } else {
                               ?>
                            <div class="BTContainer">
                                <div>
                                    <input type="text" name="comm_bill_burden" id="comm_bill_burden" value="<?php if(isset($elements[97])) { if(isset($elements[92])){echo $elements[92]; }else { echo 0; } }else{ if(isset($elements[93])){echo $elements[93]; }else { echo 0; } }?>" maxlength="9" size="10" <?php if(RATE_CALCULATOR=='Y'){?> onkeyup="doNoCalculateMarkup(this);calculatebillbtmargin();"<?php } else{ ?> onkeyup="calculatebillbtmargin();" <?php } ?>  onkeypress="return blockNonNumbers(this, event, true, false);"/>
                                </div>
                                <div>
                                    <b><span class="summaryform-formelement">%</span></b>
                                </div>
                            </div>
                        <?php
                            }
                        ?>
                    </td>
		</tr>
                <?php if(RATE_CALCULATOR=='Y'){ ?>
                 <tr id="marg-rate" >
                    <td class="summaryform-bold-title">Margin&nbsp;</td>
                    <?php
                           if ($elements[27] == '0.00' || $elements[27] == '0') {
                                $elements[27] = '';
                                $comm_margin_span = "0.00";
                            }
                            
                            if ($elements[28] == '0.00' || $elements[28] == '0') {
                                $elements[28] = '';
                                $comm_markup_span = "0.00";
                            }
                            $margin_akken = $elements[27];
                            $payrate_akken =$payRateVal;
                            $billrate_akken = $billRateVal;
                            $markup_akken = $elements[28];
                            if(isset($elements[26])){
                            $payburden_akken=  $elements[26]; 
                            }else {
                                $payburden_akken= 0;
                            }
                            $markup_akken = $elements[28];
                            if(isset($elements[97])) { 
                                if(isset($elements[92])){
                                    $billburden_akken =  $elements[92]; 
                                }else { 
                                    $billburden_akken =  0; 
                                } 
                            }else{
                                if(isset($elements[93])){
                                    $billburden_akken =  $elements[93]; 
                                }else { 
                                    $billburden_akken =  0;
                                }
                            } 
                            $calculatedValues = calculateMarginMarkupIfNotExists($payrate_akken,$billrate_akken,$payburden_akken,$billburden_akken,$margin_akken,$markup_akken);
                            $calculatedValuesArray = array();
                            $calculatedValuesArray = explode('|',$calculatedValues);
                            $elements[27] = $calculatedValuesArray[4];
                            $elements[28] = $calculatedValuesArray[5];
                            if ($billRateVal != "" && $billRateVal != "" && $elements[26] != "") {
                                $grossbillburden = ($billburden_akken / 100) * $billrate_akken;
                                $margincost=(((($billrate_akken-$grossbillburden) * 100) / 100) - ((($payrate_akken * 100) / 100) + (((($payburden_akken * 100) / 100) / 100) * (($payrate_akken * 100) / 100))));
                            } else {
                                $margincost = "0.00";
                            }
                            if(!isset($elements[28]))
                            {
                                $comm_markup_span = "0.00";
                            }
                            if(!isset($elements[27]))
                            {
                                $comm_margin_span = "0.00";
                            }
                            ?>
                    <td>
                        <span id="margin_calculator" style="cursor:pointer;display:none;"><i class="fa fa-calculator" onclick="marginCalculatorFunc(this);" aria-hidden="true"></i>&nbsp;&nbsp;</span>
                        <input type=hidden  size="10" maxlength="9" onkeypress="return blockNonNumMarginMarkup(this, event);" name=comm_margin id=comm_margin value="<?php echo dispTextdb($elements[27]);?>" class="summaryform-formelement"><span class="summaryform-formelement" style="display:none;" id="comm_margin_span"><?php echo $comm_margin_span;?></span>&nbsp;<span class="summaryform-formelement"><b>%</b></span>&nbsp;<span style="display:none;"class="summaryform-formelement">|</span>&nbsp;<span style="display:none;"class="summaryform-formelement"><b><span style="display:none;" id="margincost"><?php echo "$".$margincost;?></span></b></span>
                    <?php
                        $qry = "select netmargin from margin_setup where sno=1";
                        $qry_res = mysql_query($qry, $db);
                        $qry_row = mysql_fetch_row($qry_res);
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="summaryform-formelement">(Company&nbsp;Min&nbsp;Margin:&nbsp;<?=$qry_row[0];?>%)</span>
                    </td>
		</tr>
                <tr id="markup-rate" >
                    <td class="summaryform-bold-title">Markup&nbsp;</td>
                    <td>
                        <span id="markup_calculator" style="cursor:pointer;display:none;"><i class="fa fa-calculator" onclick="markupCalculatorFunc(this);"aria-hidden="true"></i>&nbsp;&nbsp;</span>
                        <input name="comm_markup" type="hidden" id="comm_markup" value="<?php echo html_tls_specialchars($elements[28],ENT_QUOTES);?>" size="10" maxlength="9" onkeypress="return blockNonNumMarginMarkup(this, event);" class="summaryform-formelement"><span style="display:none;" class="summaryform-formelement" id="comm_markup_span"><?php echo $comm_markup_span;?></span>&nbsp;<span class="summaryform-formelement" id="comm_markup_span_perc"><b>%</b></span></td>
		</tr>
                <?php } else { ?>
                 <tr id="marg-rate" >
                    <td class="summaryform-bold-title">Margin&nbsp;</td>
                    <?php
                        if ($elements[27] == '0.00' || $elements[27] == '0') {
                            $elements[27] = '';
                            $comm_margin_span = "0.00";
                        }
                        if ($payrateval != "" && $billrateval != "" && $elements[26] != "") {
                            $margincost = ($billrateval - ($payrateval + (($elements[26] / 100) * $payrateval)));
                        } else {
                            $margincost = "0.00";
                        }
                        if(!isset($elements[27]))
                        {
                            $comm_margin_span = "0.00";
                        }
                    ?>
                    <td><input type=hidden  maxlength=10 size=10 name=comm_margin id=comm_margin value="<?php echo dispTextdb($elements[27]);?>" class="summaryform-formelement" readonly><span class="summaryform-formelement" id="comm_margin_span"><?php echo $comm_margin_span;?></span>&nbsp;<span class="summaryform-formelement"><b>%</b></span>&nbsp;<span class="summaryform-formelement">|</span>&nbsp;<span class="summaryform-formelement"><b><span id="margincost"><?php echo "$".$margincost;?></span></b></span>
                    <?php
                        $qry = "select netmargin from margin_setup where sno=1";
                        $qry_res = mysql_query($qry, $db);
                        $qry_row = mysql_fetch_row($qry_res);
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="summaryform-formelement">(Company&nbsp;Min&nbsp;Margin:&nbsp;<?=$qry_row[0];?>%)</span>
                    </td>
		</tr>
                <tr id="markup-rate" >
                    <td class="summaryform-bold-title">Markup&nbsp;</td>
                    <?php
                    if ($elements[28] == '0.00' || $elements[28] == '0') {
                        $elements[28] = '';
                        $comm_markup_span = "0.00";
                    }
                    if(!isset($elements[28]))
                    {
                        $comm_markup_span = "0.00";
                    }
                    ?>
                    <td><input name="comm_markup" type="hidden" id="comm_markup" value="<?php echo html_tls_specialchars($elements[28],ENT_QUOTES);?>" size=10 class="summaryform-formelement"><span class="summaryform-formelement" id="comm_markup_span"><?php echo $comm_markup_span;?></span>&nbsp;<span class="summaryform-formelement" id="comm_markup_span_perc"><b>%</b></span></td>
		</tr>
                <?php }?>
               
                <tr>
                    <td colspan="2">
                        <span class="billInfoNoteStyle">Note : Rates are auto populated based on Regular (Pay/Bill) Rates/UOM. You can edit to over ride.</span>
                    </td>
                </tr>
            </table>
            </span>
            <!-- rate tab end-->
            <!-- billing info tabbed pane ends-->
            <table width="98%" border="0" class="crmsummary-jocomp-table">
		<tr id="hide-bill-rate" style=";<?php echo $hide_rate;?>"></tr>
		<tr id="hide-pay-rate" style=";<?php echo $hide_rate;?>"></tr>

		<!--salary-->
		<?php if($elements[19] == '0.00' || $elements[19] == '0')$elements[19]='';?>

		<tr id="hide-hire-sal2" style=" <?php echo $hire_sal_style2;?>">
                    <td class="summaryform-bold-title" > <span>&nbsp;Salary&nbsp;<span id="madison-req"><?=$mandatory_madison;?></span></span>&nbsp;&nbsp;</td>
                    <td>
                        <span id="leftflt">
                            <input class="summaryform-formelement" type=text id=salary name=salary size=10  maxlength="9" value="<?php if($elements[19] == '0.00') echo ""; else echo html_tls_specialchars($elements[19],ENT_QUOTES);?>" onkeypress="return blockNonNumbers(this, event, true, false);">&nbsp;
                            <!-- UOM:get dynamic ratetypes-->
                                <select name="perbill" onClick="getPreviousRate(this);" onChange="change_Period('salary');" id="perbill" class="summaryform-formelement">
                                    <?php  
                                     if($elements[21]==''){
                                     $assignRatePeriods = getNewRateTypes('YEAR');    
                                     }else {
                                      $assignRatePeriods = getNewRateTypes($elements[21]);      
                                     }
                                    echo  $assignRatePeriods;        
                                    ?>
                                </select>
                            &nbsp;
                            <select name="paybill" class="summaryform-formelement">
                            <?php
                            displayCurrency($elements[20]);
                            ?>
                            </select>
                        </span>
                    </td>
		</tr>
		<!--salary-->
		<?php
		$arr = $objMRT->getRateTypeById(2);
                if($arr['peditable'] == 'N')
                {
                    $pdisabled_user_input_field = ' disabled_user_input_field';
                    $pdisabled_user_input_radio_button = 'pdisabled_user_input_radio_button';
                    $penabled_user_input_radio_button = 'penabled_user_input_radio_button';
                    $pdisable = ' disabled="disabled"';
                    $assingBillFlagP = 'onclick=getPreviousRate(this);  onChange=change_Period("overtimepayrated");';
                    
                }
                else
                {
                    $pdisabled_user_input_field = '';
                    $pdisabled_user_input_radio_button = '';
                    $penabled_user_input_radio_button = '';
                    $pdisable = '';
                    $assingBillFlagP = 'onclick=getPreviousRate(this);  onChange=change_Period("overtimepayrate");';
                    $assingBillFlagC = 'onChange=change_PeriodNew("overtimepayratecur");';
                }
                if($arr['beditable'] == 'N')
                {
                    $bdisabled_user_input_field = ' disabled_user_input_field';
                    $bdisable = ' disabled="disabled"';
                }
                else
                {
                    $bdisabled_user_input_field = '';
                    $bdisable = '';
                }
		?>
		<tr id="bill_info_overtimepay" style=" <?php echo $Dir_Int_pay;?>">
                    <td width="13%" class="summaryform-bold-title"><?php echo $arr['name'];?> Pay Rate
                        <span id="madison-req-otpr">&nbsp;<?=$mandatory_madison;?></span>
                    </td>
                    <td>
                        <?php if($elements[52] == '0.00' || $elements[52] == '0')?>
                        <span id="leftflt">
                            <input class="summaryform-formelement<?php echo $pdisabled_user_input_field;?>" type="text" size=10 id="otrate_pay" name="otrate_pay" value="<?php if($elements[52] == '0.00') echo ""; else echo $elements[52];?>" maxlength="9" <?php echo $pdisable;?> onkeypress="return blockNonNumbers(this, event, true, false);"><input type="hidden" id="otrate_pay_hidden" value="<?php echo $arr['pvalue'];?>">
                        </span>
                        <span id="leftflt">&nbsp;
                        <!-- UOM:get dynamic ratetypes-->
                            <select name="perotrate_pay" id="perotrate_pay" <?php echo $assingBillFlagP; ?>  class="summaryform-formelement<?php echo $pdisabled_user_input_field;?>" <?php echo $pdisable;?>>
                        <?php   $assignRatesDisp = getNewRateTypes($elements[53]); 
                                echo  $assignRatesDisp;
                        ?> 
                            </select>
                        </span>
                        <span id="leftflt">&nbsp;
                            <select name="payotrate_pay" id="payotrate_pay"  <?php echo $assingBillFlagC; ?> class="summaryform-formelement<?php echo $pdisabled_user_input_field;?>" <?php echo $pdisable;?>>
                            <?php
                            displayCurrency($elements[54]);
                            ?>
                            </select>
                        </span>
			<span id='ot_pay_Bill_NonBill'>
                        <input name="OvpayrateBillOpt" id="OvpayrateBillOpt"  type="radio" value="Y" <?php if($ratesDefaultVal['OverTime'][0] == ''){ if($arr['poption'] == "B"){ echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"'; }else { echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';} } else if($ratesDefaultVal['OverTime'][0] == 'Y'){echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"';}else{ echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';}?>><font class="afontstyle">Billable</font>
                        <input name="OvpayrateBillOpt" id="OvpayrateBillOpt"  type="radio" value="N" <?php if($ratesDefaultVal['OverTime'][0] == ''){ if($arr['poption'] == "NB"){ echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"'; }else { echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';} } else if($ratesDefaultVal['OverTime'][0] == 'N'){echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"';}else{ echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';}?>><font class="afontstyle">Non-Billable</font>
			</span>
                    </td>
		</tr>
		<tr id="bill_info_overtimebill" style=" <?php echo $Dir_Int_bill;?>">
                    <td width="13%" class="summaryform-bold-title"><?php echo $arr['name'];?> Bill Rate&nbsp;<?=$mandatory_madison;?></td>
                    <td>
                        <?php if($elements[55] == '0.00' || $elements[55] == '0')?>
                        <span id="leftflt">
                            <input class="summaryform-formelement<?php echo $bdisabled_user_input_field;?>" type="text" size=10 id="otrate_bill" name="otrate_bill" value="<?php if($elements[55] == '0.00') echo ""; else echo $elements[55];?>" maxlength="9" <?php echo $bdisable;?> onkeypress="return blockNonNumbers(this, event, true, false);"><input type="hidden" id="otrate_bill_hidden" value="<?php echo $arr['bvalue'];?> ">
                        </span>
                        <span id="leftflt">&nbsp;
                        <!-- UOM:get dynamic ratetypes-->
                            <select name="perotrate_bill" id="perotrate_bill" onclick="getPreviousRate(this);"  onChange="change_Period('overtimebillrate');"    class="summaryform-formelement<?php echo $bdisabled_user_input_field;?>" <?php echo $bdisable;?>>
                                <?php   $assignRatesDisp = getNewRateTypes($elements[56]); 
                                        echo  $assignRatesDisp;
                                ?> 
                            </select>
                        </span>
                        <span id="leftflt">&nbsp;
                            <select name="payotrate_bill" id="payotrate_bill"  onChange="change_PeriodNew('overtimebillratecur');" class="summaryform-formelement<?php echo $bdisabled_user_input_field;?>" <?php echo $bdisable;?>>
                            <?php
                            displayCurrency($elements[57]);
                            ?>
                            </select>
                        </span>
			<span id='ot_bill_Tax_NonTax'>
                        <input name="OvbillrateTaxOpt" id="OvbillrateTaxOpt"  type="radio" value="Y" <?php if($ratesDefaultVal['OverTime'][1] == ''){ if($arr['boption'] == "T"){ echo ' checked="checked"'; }else { echo $bdisable; } } else if($ratesDefaultVal['OverTime'][1] == 'Y'){echo ' checked="checked"';}else{ echo $bdisable;}?>><font class="afontstyle">Taxable</font>
                        <input name="OvbillrateTaxOpt" id="OvbillrateTaxOpt"  type="radio" value="N" <?php if($ratesDefaultVal['OverTime'][1] == ''){ if($arr['boption'] == "NT"){ echo ' checked="checked"'; }else { echo $bdisable; } } else if($ratesDefaultVal['OverTime'][1] == 'N'){echo ' checked="checked"';}else{ echo $bdisable;}?>><font class="afontstyle">Non-Taxable</font>
			</span>
                    </td>
		</tr>
		<?php
                    $arr = $objMRT->getRateTypeById(3);
                    if($arr['peditable'] == 'N')
                    {
                        $pdisabled_user_input_field = ' disabled_user_input_field';
                        $pdisabled_user_input_radio_button = 'pdisabled_user_input_radio_button';
                        $penabled_user_input_radio_button = 'penabled_user_input_radio_button';
                        $pdisable = ' disabled="disabled"';
                       $assingBillFlagP =  'onclick=getPreviousRate(this);  onChange=change_Period("dbtimepayrated");';
                       
                    }
                    else
                    {
                        $pdisabled_user_input_field = '';
                        $pdisabled_user_input_radio_button = '';
                        $penabled_user_input_radio_button = '';
                        $pdisable = '';
                        $assingBillFlagP =  'onclick=getPreviousRate(this);  onChange=change_Period("dbtimepayrate");';
                        $assingBillFlagC =  'onChange=change_PeriodNew("dbtimepayratecur");';
                    }
                    if($arr['beditable'] == 'N')
                    {
                        $bdisabled_user_input_field = ' disabled_user_input_field';
                        $bdisable = ' disabled="disabled"';
                    }
                    else
                    {
                        $bdisabled_user_input_field = '';
                        $bdisable = '';
                    }
		?>
		<tr id="db_time_payrate" >
                    <td  width="13%" class="summaryform-bold-title" nowrap><?php echo $arr['name'];?> Pay Rate&nbsp;<span id="madison-req-dtpr"><?=$mandatory_madison;?></span></td>
                    <td>
                        <span id="leftflt"><input name="db_time_pay" type="text" id="db_time_pay" value="<?php if($elements[61] == '0.00') echo ""; else echo $elements[61];?>" size=10 maxlength="9" class="summaryform-formelement<?php echo $pdisabled_user_input_field;?>" <?php echo $pdisable;?> onkeypress="return blockNonNumbers(this, event, true, false);"><input type="hidden" id="db_time_pay_hidden" value="<?php echo $arr['pvalue'];?>"></span>
                        <span id="leftflt">&nbsp;
                            <!-- UOM:get dynamic ratetypes-->
                                <select name="db_time_payper" <?php echo $assingBillFlagP ;?>   id="db_time_payper" class="summaryform-formelement<?php echo $pdisabled_user_input_field;?>" <?php echo $pdisable;?>>
                                <?php   $assignRatesDisp = getNewRateTypes($elements[62]); 
                                        echo  $assignRatesDisp ;
                                ?> 
                                 </select>
                            </span>
                            <span id="leftflt">&nbsp;
                            <select name="db_time_paycur" id="db_time_paycur" <?php echo $assingBillFlagC ; ?> class="summaryform-formelement<?php echo $pdisabled_user_input_field;?>" <?php echo $pdisable;?>>
                                <?php
                                displayCurrency($elements[63]);
                                ?>
                            </select>
                            </span>
			    <span id='dt_pay_Bill_NonBill'>
                            <input name="DbpayrateBillOpt" id="DbpayrateBillOpt"  type="radio" value="Y" <?php if($ratesDefaultVal['DoubleTime'][0] == ''){  if($arr['poption'] == "B"){ echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"';}else { echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';} }else if($ratesDefaultVal['DoubleTime'][0] == 'Y'){echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"';}else{ echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';}?>><font class="afontstyle">Billable</font>
                           <input name="DbpayrateBillOpt" id="DbpayrateBillOpt"  type="radio" value="N" <?php if($ratesDefaultVal['DoubleTime'][0] == ''){  if($arr['poption'] == "NB"){ echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"';}else { echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';} }else if($ratesDefaultVal['DoubleTime'][0] == 'N'){echo ' checked="checked"'; echo 'class="'.$penabled_user_input_radio_button.' BillableRates"';}else{ echo $pdisable; echo 'class="'.$pdisabled_user_input_radio_button.' BillableRates"';}?>><font class="afontstyle">Non-Billable</font>
			   <span id='reg_pay_Bill_NonBill'>
                    </td>
		</tr>

		<tr id="db_time_billrate" >
                    <td  width="13%" class="summaryform-bold-title"><?php echo $arr['name'];?> Bill Rate&nbsp;<?=$mandatory_madison;?></td>
                    <td>
                        <span id="leftflt">
                        <input name="db_time_bill" type="text" id="db_time_bill" value="<?php if($elements[64] == '0.00') echo ""; else echo $elements[64];?>" size=10 maxlength="9" class="summaryform-formelement<?php echo $bdisabled_user_input_field;?>" <?php echo $bdisable;?> onkeypress="return blockNonNumbers(this, event, true, false);"><input type="hidden" id="db_time_bill_hidden" value="<?php echo $arr['bvalue']; ?>">
                        </span>
                        <span id="leftflt">&nbsp;
                        <!-- UOM:get dynamic ratetypes-->
                        <select name="db_time_billper" id="db_time_billper"  onclick="getPreviousRate(this);"  onChange="change_Period('dbtimebillrate');" class="summaryform-formelement<?php echo $bdisabled_user_input_field;?>" <?php echo $bdisable;?>>
                                <?php   $assignRatesDisp = getNewRateTypes($elements[65]); 
                                        echo  $assignRatesDisp ;
                                ?> 
                        </select>
                        </span>
                        <span id="leftflt">&nbsp;
                        <select name="db_time_billcurr" id="db_time_billcurr" onChange="change_PeriodNew('dbtimebillratecur');" class="summaryform-formelement<?php echo $bdisabled_user_input_field;?>" <?php echo $bdisable;?>>
                        <?php
                        displayCurrency($elements[66]);
                        ?>
                        </select>
                        </span>
			<span id='db_bill_Tax_NonTax'>
                        <input name="DbbillrateTaxOpt" id="DbbillrateTaxOpt"  type="radio" value="Y" <?php if($ratesDefaultVal['DoubleTime'][1] == ''){ if($arr['boption'] == "T"){ echo ' checked="checked"'; }else { echo $bdisable; } }else if($ratesDefaultVal['DoubleTime'][1] == 'Y'){echo ' checked="checked"';}else{ echo $bdisable;}?>><font class="afontstyle">Taxable</font>
                        <input name="DbbillrateTaxOpt" id="DbbillrateTaxOpt"  type="radio" value="N" <?php if($ratesDefaultVal['DoubleTime'][1] == ''){ if($arr['boption'] == "NT"){ echo ' checked="checked"'; }else { echo $bdisable; } }else if($ratesDefaultVal['DoubleTime'][1] == 'N'){echo ' checked="checked"';}else{ echo $bdisable;}?>><font class="afontstyle">Non-Taxable</font>
			</span>
                    </td>
		</tr>
		<tr>
                    <td colspan="2" style="padding:0px">
                        <div id="multipleRatesTab"></div>
                        <input type="hidden" id="selectedcustomratetypeids" value="<?php echo $customRateIds;?>">
                    </td>
		</tr>
                <tr id="custom_rate_type_tr" style="display:none;">
                    <td colspan="2">
                        <a class="crm-select-link" href="javascript:addRateTypes();">Select Custom Rate</a>
                    </td>
                </tr>
		<tr id="billable_block" style="">
			<td colspan="2" align="left">
			<table width="97%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td width="20%" align="left" valign="top" class="summaryform-bold-title" widtd="10%">&nbsp;</td>
				<td width="9%" align="left" valign="top" class="summaryform-bold-title" widtd="10%">Lodging</td>
				<td width="5%" align="left" valign="top" class="summaryform-bold-title" widtd="8%">M&amp;IE</td>
				<td width="6%" align="left" valign="top" class="summaryform-bold-title" widtd="15%">Total</td>
				<td width="30%" align="left" valign="top" class="summaryform-bold-title" widtd="27%">&nbsp;</td>
				<td width="30%" align="left" class="summaryform-bold-title" valign="top">&nbsp;</td>
			</tr>
			<tr>
				<td align="left" class="summaryform-bold-title">Per Diem</td>
				<td align="left"><input type="text" name="txt_lodging" id="txt_lodging" value="<?php echo html_tls_specialchars($elements[71],ENT_QUOTES);?>" size="5" onBlur="calculatePerDiem()" class="summaryform-formelement"/></td>
				<td align="left"><input type="text" name="txt_mie" id="txt_mie" value="<?php echo html_tls_specialchars($elements[72],ENT_QUOTES);?>" onBlur="calculatePerDiem()" size="5" class="summaryform-formelement"/></td>
				<td align="left"><input type="text" name="txt_total" id="txt_total" value="<?php echo html_tls_specialchars($elements[73],ENT_QUOTES);?>" onBlur="calculatePerDiem()" size="8" class="summaryform-formelement"/></td>
				<td align="left">
                                <!-- UOM:get dynamic ratetypes-->
                                <select name="sel_perdiem" id="sel_perdiem"  onClick="getPreviousRate(this);" onChange="change_Period('selperdiem');" class="summaryform-formelement">
                                    <?php   $assignRatesDisp = getNewRateTypes($sel_perdiem_per); 
                                            echo  $assignRatesDisp;
                                    ?> 
                                </select>&nbsp;&nbsp;
					<select name="sel_perdiem2" id="sel_perdiem2" class="summaryform-formelement">
					<?php
					displayCurrency($sel_perdiem_cur);
					?>
					</select>
				</td>
				<td align="left"  valign="middle">
					<span id="leftflt" class="summaryform-nonboldsub-title" style="font-size: 12px"><input class="summaryform-formelement" type="radio" name="radio_taxabletype" value="Y" <?php echo sent_check("Y",$elements[77]);?>>Taxable</span>
					<span id="leftflt" class="summaryform-nonboldsub-title"><input class="summaryform-formelement" type="radio" name="radio_taxabletype" value="N" <?php echo sent_check("N",$elements[77]);?>>Non-Taxable</span>
				</td>
			</tr>
			<tr>
				<td width="16%" align="left" valign="top" class="summaryform-bold-title" widtd="10%">&nbsp;</td>
				<td align="left" valign="middle" colspan="5">
					<div id="leftflt" class="summaryform-nonboldsub-title" align="left" style="float:left;font-size: 12px;"><input class="summaryform-formelement" type="radio" name="radio_billabletype" value="Y" <?php echo sent_check("Y",$elements[76]);?> onClick="javascript:showBillDiv(this,'txt_total');" >Billable</div>
					<?php 
					$style = ($elements[76]=="Y") ? 'style="float:left;"' : 'style="float:left; display: none;"';
					?>
					<div align="left" id="bill_Div" <?php echo $style; ?>>
					&nbsp;<input type="text" name="diem_billrate" id="diem_billrate" size="8" maxlength="9" onBlur="javascript:isNumbervalidation(this,'Billrate');" value="<?php echo $elements[79];?>" class="summaryform-formelement" />
					</div>
					<div align="left" style="float:left" id="leftflt" class="summaryform-nonboldsub-title">&nbsp;<input class="summaryform-formelement" type="radio" name="radio_billabletype" value="N" <?php echo sent_check("N",$elements[76]);?> onClick="javascript:showBillDiv(this);">Non-Billable
					</div>
				</td>
			</tr>		
			</table>
			</td>
		</tr>

            <input type="hidden" name="otrate">
            <input type="hidden" name="perotrate">
            <input type="hidden" name="payotrate">

            <tr id="jobloc_deduct" >
                <td  width="13%" class="summaryform-bold-title" nowrap>&nbsp;</td>
                <td><span id="leftflt" class="summaryform-bold-title"><input type="checkbox" name="use_jobloc_deduct" value='<?php echo $elements[69];?>' <?php echo sent_check($elements[69],'Y');?>>&nbsp;Use Job Location for Applicable Taxes</span></td>
            </tr>

            <tr>
                <td width="13%" class="summaryform-bold-title">Placement Fee</td>
                <td>
                    <?php if($elements[34] == '0.00' || $elements[34] == '0')$elements[34]='';?>
                    <span id="leftflt"><input class="summaryform-formelement" type="text" size=15 name="pfee" value="<?php if($elements[34]=='0.00') echo ""; else  echo $elements[34];?>" maxlength="9"></span>
                    <span id="leftflt">&nbsp;
                    <select name="payfee" class="summaryform-formelement">
                    <?php
                    displayCurrency($elements[35]);
                    ?>
                    </select>
                    </span>
                </td>
            </tr>
            <?php if(REFERRAL_BONUS_MANAGE=='ENABLED' && $referral_bonus_exists=='YES' && $copyasign!='yes'){ ?>
             <tr>
		<td width="13%" class="summaryform-bold-title">Referral Bonus</td>
		<td>
			<span id="leftflt"><input class="summaryform-formelement" type="text" size=15 name="ref_bonus_amount" disabled value="<?php echo $ref_bonus_row[0];?>"></span>
		</td>
             </tr>
         <?php } ?>
            <tr>
                    <td colspan="2"><span id="leftflt"><span class="summaryform-bold-title">Commission</span></span></td>
            </tr>
        <tr>
        <td colspan="2">
            <table border="0" width=100%>
                <thead>
			<tr>
				<input type="hidden" name="empvalues" id="empvalues">
				<td width="148" class="summaryrow" style="border-bottom: 0px solid #ddd;text-align: left;">Add Person:</td>
				<td style="border-bottom: 0px solid #ddd; padding-left:0px; text-align:left !important" colspan="3">
					<span id="leftflt">
						<select name="addemp" class="summaryform-formelement setcommrolesize" onChange="addCommission('newrow')">
							<option selected  value="">--select employee--</option>
							<?php
								while($row=mysql_fetch_row($res))
									print '<option '.compose_sel($elements[13],$row[0]).' value="'.'emp'.$row[0].'|'.$row[1].'">'.stripslashes($row[2].' - '.$row[1]).'</option>';
							?>
						</select>
					</span>
					<span class="summaryform-formelement">&nbsp;|&nbsp;</span>
					<a class="crm-select-link" href="javascript:contact_popup1('comcontact')"><strong>select</strong> contact</a>
					&nbsp;<a href="javascript:contact_popup1('comcontact')">
						<i alt='Search' class='fa fa-search'></i>
					</a>
					<span class="summaryform-formelement">&nbsp;|&nbsp;</span>
					<a class="crm-select-link" href="javascript:newScreen('contact','comcontact')"><strong>new</strong> contact</a>
				</td>
			</tr>
                </thead>
                <tbody id="commissionRows">
                <tr>
                        <?php
                            //if($_SESSION['ACC_AS_SESSIONRN'] != '')
				if($apprn != '')
					$commission_session	= $_SESSION['commission_session'.$apprn];

				$comm_data	= explode("|",$commission_session);
				$empno		= explode(",",$elements[51]);
				$emptxt		= explode(",",$comm_data[0]);
				$rateval	= explode(",",$comm_data[1]);
				$payval		= explode(",",$comm_data[2]);
				$roleval	= explode(",",$comm_data[4]);
				$overwriteval	= explode(",",$comm_data[5]);
				$eUserInput	= explode(",",$comm_data[6]);

				for($p=0; $p<=$elements[50]; $p++) {
					$tmpempId	= str_replace('emp','',$empno[$p]);
					$subroles_query = "SELECT empid, type FROM `entity_submission_roledetails` WHERE empid ='".$tmpempId."' and posid='".$jobPosVal."' order by sno desc limit 1";
					$subroles_res	= mysql_query($subroles_query,$db);
					$subroles	= mysql_fetch_row($subroles_res);

					if(substr($empno[$p],0,3)=='emp') {
						$emp_val	= explode("emp",$empno[$p]);
	    
						if($subroles[1] != 'A') {
							$sel_emp	= "SELECT name FROM emp_list WHERE username='".$emp_val[1]."'";
							$res_emp	= mysql_query($sel_emp,$db);
							$fetch_emp	= mysql_fetch_row($res_emp);
							$commName	= stripslashes($fetch_emp[0]);
						}
						else {
							$sel_acc	= "SELECT CONCAT_WS('',staffacc_contact.fname,'',staffacc_contact.lname,IF(staffacc_cinfo.cname!='',concat('(',staffacc_cinfo.cname,')'),'')) FROM staffacc_contact LEFT JOIN staffacc_cinfo ON staffacc_contact.username = staffacc_cinfo.username AND staffacc_cinfo.type IN ('CUST','BOTH') WHERE staffacc_contact.sno='".$emp_val[1]."' and staffacc_contact.acccontact='Y' and staffacc_contact.username!=''";
							$res_acc	= mysql_query($sel_acc,$db);
							$fetch_acc	= mysql_fetch_row($res_acc);
							$commName	= stripslashes($fetch_acc[0]);
						}
					}
					else {
						$sel_acc	= "SELECT CONCAT_WS('',staffacc_contact.fname,'',staffacc_contact.lname) FROM staffacc_contact LEFT JOIN staffacc_cinfo ON staffacc_contact.username = staffacc_cinfo.username AND staffacc_cinfo.type IN ('CUST','BOTH') WHERE staffacc_contact.sno='".$empno[$p]."' and staffacc_contact.acccontact='Y' and staffacc_contact.username!=''";
						$res_acc	= mysql_query($sel_acc,$db);
						$fetch_acc	= mysql_fetch_row($res_acc);
						$commName	= stripslashes($fetch_acc[0]);
					}

					$comm_roletitle = '';

					if(!in_array($roleval[$p],$rolesSelectIds))
					{
						$role_sel	= "SELECT roletitle  FROM company_commission WHERE sno =".$roleval[$p];
						$role_sel_res	= mysql_query($role_sel,$db);
						$role_fetch	= mysql_fetch_row($role_sel_res);

						if($role_fetch[0] !='')
							$comm_roletitle	= $role_fetch[0];
					}

					$rs		= "SELECT enable_details FROM company_commission WHERE sno =".$roleval[$p];
					$res		= mysql_query($rs,$db);
					$res_result	= mysql_fetch_row($res);
					$comm_enable_details	= $res_result[0];

					if($empno[$p] != '') {
					?>
					<script>
						var empsno	= "<?php echo $empno[$p];?>";
						var emptext	= "<?php echo $emptxt[$p];?>";

						var ratetxt	= "<?php echo $rateval[$p];?>";
						var paytxt	= "<?php echo $payval[$p];?>";
						var commname	= "<?php echo $commName;?>";
						var roletxt	= "<?php echo $roleval[$p];?>";
						var overwritetxt= "<?php echo $overwriteval[$p];?>";
						var euserinput	= "<?php echo $eUserInput[$p];?>";

						if(empsno != "noval") {
							addRow(commname+"|akkenSplit|"+emptext+"|akkenSplit|"+ratetxt+"|akkenSplit|"+paytxt+"|akkenSplit|"+roletxt+"|akkenSplit|"+overwritetxt+"|akkenSplit|"+euserinput,empsno,'edit');
							var rnval	= eval("document.forms[0].roleName"+'<?php echo $p;?>');
							var rval	= eval("document.forms[0].ratetype"+'<?php echo $p;?>');
							var pval	= eval("document.forms[0].paytype"+'<?php echo $p;?>');

							if('<?php echo $comm_roletitle;?>' != '')
							{
								var oOption	= document.createElement("option");
								oOption.appendChild(document.createTextNode('<?php echo $comm_roletitle;?>'));
								oOption.setAttribute("value", roletxt);
								rnval.appendChild(oOption);
							}

							SetSelectedIndexSelect(rnval,roletxt);
						}

						if(euserinput == 'N') {
							document.getElementById("commval"+'<?php echo $p;?>').disabled	= true;
						}

						if('<?php echo $comm_enable_details;?>' == 'N') {
							document.getElementById("commval"+'<?php echo $p;?>').style.visibility	= 'hidden';
							document.getElementById("perflat_"+'<?php echo $p;?>').style.visibility	= 'hidden';
						}
					</script>
					<?php
					}
				}
                        ?>
                </tr>
		<tr>
			<td colspan="4">
				<span class="commRolesNoteStyle">
					Note : If no role is selected for an employee, such records will not be saved.
				</span>
			</td>
		</tr>
		</table>
                </td>
                </tr>
		<tr>
			<td class="summaryform-bold-title">Payroll Provider ID#</td>
			<td><span id="leftflt"><input class="summaryform-formelement" type="text" size=20 name="payroll" value="<?php if($elements[39] == '0.00') echo ""; else echo html_tls_specialchars($elements[58],ENT_QUOTES);?>" maxlength="20"></span></td>
		</tr>
		<tr>
			<td class="summaryform-bold-title">Workers Comp Code<?php if($mandatory_madison == "") { echo $mandatory_synchr_akkupay; } else { echo "&nbsp;".$mandatory_madison; } ?></td>
			<td>
                            <span id="leftflt">
                            <select class="summaryform-formelement" name="workcode" id="workcode" style="width:210px" setName='Workers Comp Code' <?php echo $spl_Attribute?>>
                            <option value=""> -- Select (Code-Title-State) -- </option>
                            <?php
                            getDispWCOptions($elements[39]);	// Displaying Worker Compcode options.
                            ?>
                            </select>&nbsp;
                            <?php
                            if(ENABLE_MANAGE_LINKS == 'Y')
                                    echo '<a href="javascript:doAddWorkersCompCode(\'workcode\')" class="crm-select-link">Add</a>';
                            ?>
                            </span>
			</td>
		</tr>
		<tr>
			<td class="summaryform-bold-title">Payment Terms</td>
			<td>
				<?php
				$BillPay_Sql = "SELECT billpay_termsid, billpay_code FROM bill_pay_terms WHERE billpay_status = 'active' AND billpay_type = 'PT' ORDER BY billpay_code";
				$BillPay_Res = mysql_query($BillPay_Sql,$db);
				?>
				<select name="pterms" id="pterms" style="width:210px;">
				<option value=""> -- Select -- </option>
				<?php  
				while($BillPay_Data = mysql_fetch_row($BillPay_Res))
				{ 
					?>
					<option value="<?=$BillPay_Data[0];?>" <?php echo sele($elements[30],$BillPay_Data[0]); ?> title="<?=html_tls_specialchars(stripslashes($BillPay_Data[1]),ENT_QUOTES);?>"><?=stripslashes($BillPay_Data[1]);?></option>
					<?php 
				}
				?>
				</select>
				<?php 
				if(ENABLE_MANAGE_LINKS == 'Y')
					echo '&nbsp;&nbsp;&nbsp;<a href="javascript:doManageBillPayTerms(\'Payment\',\'pterms\')" class="edit-list">Manage</a>';
				?>
            </td>
		</tr>
		<!-- <tr>
			<td class="summaryform-bold-title">Timesheet Approval</td>
			<td>
				<span id="leftflt" class="summaryform-nonboldsub-title" style="font-size: 12px;"><input class="summaryform-formelement" type="radio" name="tapproval" id="tapproval" value="Manual"  <?php // if($elements[41] != '') echo sent_check("Manual",$elements[41]);else echo'checked';?>>Manual</span>
				<span id="leftflt" class="summaryform-nonboldsub-title">&nbsp;&nbsp;&nbsp;<input class="summaryform-formelement" type="radio" name="tapproval" id="tapproval" value="Online" <?php //echo sent_check("Online",$elements[41]);?>>Online</span>
			</td>
		</tr> -->
		<span class="summaryform-bold-title" style="visibility: hidden;">
			<span id="leftflt" class="summaryform-nonboldsub-title" style="font-size: 12px;"><input class="summaryform-formelement" type="radio" name="tapproval" id="tapproval" value="Manual"  <?php  if($elements[41] != '') echo sent_check("Manual",$elements[41]);else echo'checked';?>>Manual</span>
			<span id="leftflt" class="summaryform-nonboldsub-title">&nbsp;&nbsp;&nbsp;<input class="summaryform-formelement" type="radio" name="tapproval" id="tapproval" value="Online" <?php echo sent_check("Online",$elements[41]);?>>Online</span>
		</span>
		<tr>
			<td width="167" class="summaryform-bold-title">PO Number</td>
			<td><span id="leftflt"><input class="summaryform-formelement" type="text" name="po_num" size=20 value="<?php echo html_tls_specialchars(stripslashes($elements[67]),ENT_QUOTES);?>" maxlength="255"></span></td>
		</tr>
		<tr>
			<td width="167" class="summaryform-bold-title">Department</td>
			<td><span id="leftflt"><input class="summaryform-formelement" type="text" name="assg_dept" size=20 value="<?php echo html_tls_specialchars(stripslashes($elements[68]),ENT_QUOTES);?>" maxlength="255"></span></td>
		</tr>
		<tr>
			<td width="167" class="summaryform-bold-title">Attention</td><td><input type="text" maxlength="255" value="<?php echo html_tls_specialchars($elements[89],ENT_QUOTES);?>" name="attention" size="20" class="summaryform-formelement"></td>
		</tr>

		<?php
		$billcontact=$elements[37];
		$bill_loc=$elements[38];
		if ($billcontact!=0) {

			$que2="SELECT CONCAT_WS( ' ', staffacc_contact.fname, staffacc_contact.mname, staffacc_contact.lname ),staffacc_cinfo.sno,staffacc_cinfo.username FROM staffacc_contact LEFT JOIN staffacc_cinfo on staffacc_contact.username = staffacc_cinfo.username AND staffacc_cinfo.type IN ('CUST','BOTH') LEFT JOIN staffacc_list ON staffacc_list.username = staffacc_cinfo.username WHERE staffacc_contact.sno ='".$billcontact."' AND staffacc_list.status='ACTIVE' AND staffacc_contact.acccontact='Y' and staffacc_contact.username!=''";

			$res2=mysql_query($que2,$db);

			$row2=mysql_fetch_row($res2);

			$billcont=$billcont = $row2[0];

			$billcompany=$row2[1];

			$billcont_stat=$row2[2];

		} elseif($bill_loc > 0 && ($billcontact==0 || $billcontact=="")) {

			$que2="select csno from staffacc_location where sno='".$bill_loc."' and ltype in ('com','loc')";

			$res2=mysql_query($que2,$db);

			$row2=mysql_fetch_row($res2);

			$billcompany=$row2[0];
		}
		$cus_username="select username from staffacc_cinfo where sno='".$billcompany."'";
                $cus_username_res=mysql_query($cus_username,$db);
                $cust_username=mysql_fetch_row($cus_username_res);
                $custusername=$cust_username[0];
		?>
        
        <tr>
			<input type="hidden" name="billcompany_sno" id="billcompany_sno" value="<?php echo $billcompany;?>">
			<input type="hidden" name="billcompany_username" id="billcompany_username" value="<?php echo $custusername;?>">
			<td width="167" class="summaryform-bold-title">Billing Address</td>
			<td><span id="billdisp_comp"><input type="hidden" name="bill_loc" id="bill_loc"><a class="crm-select-link" href="javascript:bill_jrt_comp('bill')">select company</a>&nbsp;</span></span>&nbsp;<span id="billcomp_chgid">&nbsp;</span></td>
		</tr>

		<tr>
			<input type="hidden" name="billcontact_sno" id="billcontact_sno" value="<?php echo $billcontact;?>">
			<td width="167" class="summaryform-bold-title">
				Billing Contact
				<div id="dateSelGridDiv" class="weekrule" style="display: inline;">
<a href="#" class="tooltip"><i class="fa fa-info-circle"></i><span style="width:350px"><table class="notestooltiptable" width="350px" height="40"><tbody><tr><td class="notestooltip" style="text-align:left" align="center"><div style="font-weight: normal; line-height: 22px; font-size:11px !important;">Invoices generated using "Create Invoices by Billing Contact-ASGN" ONLY will be email delivered to this billing contact.</div></td></tr></tbody></table></span></a></div>
			</td>
			<td>
			<?php
			if ($billcontact==0) {
			?>
				<span id="billdisp"><a class="crm-select-link" href="javascript:bill_jrt_cont('bill')">select contact</a></span>
				&nbsp;<span id="billchgid"><a href="javascript:bill_jrt_cont('bill')"><i alt='Search' class='fa fa-search'></i></a>
				<!--<span class="summaryform-formelement">&nbsp;|&nbsp;</span><a class="crm-select-link" href="javascript:donew_add('bill')">new&nbsp;contact</a>-->
				</span>
			<?php
			} else { 
			?>
				<span id="billdisp"><a class="crm-select-link" href="javascript:contact_func('<?php echo $billcontact;?>','<?php echo $billcont_stat;?>','bill')"><?php echo $billcont;?></a></span>
				&nbsp;<span id="billchgid"><span class=summaryform-formelement>(</span> <a class=crm-select-link href=javascript:bill_jrt_cont('bill')>change </a>&nbsp;<a href=javascript:bill_jrt_cont('bill')><i alt='Search' class='fa fa-search'></i></a>
				<!--<span class=summaryform-formelement>&nbsp;|&nbsp;</span><a class=crm-select-link href=javascript:donew_add('bill')>new</a>-->
				<span class=summaryform-formelement>&nbsp;|&nbsp;</span><a class="crm-select-link" href="javascript:removeContact('bill')">remove&nbsp;</a><span class=summaryform-formelement>&nbsp;)&nbsp;</span></span>

			<?php
			}
			?>
			</td>
		</tr>
		
		<tr>
			<td width="167" valign="top" class="summaryform-bold-title">Billing Terms</td>
			<td>
				<?php
				$BillPay_Sql = "SELECT billpay_termsid, billpay_code FROM bill_pay_terms WHERE billpay_status = 'active' AND billpay_type = 'BT' ORDER BY billpay_code";
				$BillPay_Res = mysql_query($BillPay_Sql,$db);
				?>
				<select name="billreq" id="billreq" style="width:210px;">
				<option value=""> -- Select -- </option>
				<?php  
				while($BillPay_Data = mysql_fetch_row($BillPay_Res))
				{ 
					?>
					<option value="<?=stripslashes($BillPay_Data[0]);?>" <?php echo sele($elements[42],$BillPay_Data[0]); ?> title='<?=html_tls_specialchars(stripslashes($BillPay_Data[1]),ENT_QUOTES);?>'><?=stripslashes($BillPay_Data[1]);?></option>
					<?php 
				}
				?>
				</select>
				<?php 
				if(ENABLE_MANAGE_LINKS == 'Y')
					echo '&nbsp;&nbsp;&nbsp;<a href="javascript:doManageBillPayTerms(\'Billing\',\'billreq\')" class="edit-list">Manage</a>';
				?>
			</td>
		</tr>
		<tr>
			<td width="167" valign="top" class="summaryform-bold-title">Service Terms</td>
			<td><textarea name="servterms" cols="60" rows="5" id="servterms" ><?php echo html_tls_specialchars(stripslashes($elements[43]),ENT_QUOTES);?></textarea></td>
		</tr>
		</table>
		</div>

		<?php
		if($mode=="editassign" && PAYROLL_PROCESS_BY == "VERTEX")
		{
			$sqlEmpGeoCode = "SELECT vprt_GeoCode, username FROM emp_list WHERE sno=".$empid;
			$resEmpGeoCode = mysql_query($sqlEmpGeoCode,$db);
			$rowEmpGeoCode = mysql_fetch_row($resEmpGeoCode);

			if($rowEmpGeoCode[0] == '' || $rowEmpGeoCode[0] == '0')
			{
				$getZipEmp = "SELECT zip FROM hrcon_general WHERE username = '".$rowEmpGeoCode[1]."' AND ustatus = 'active'";
				$resZipEmp = mysql_query($getZipEmp,$db);
				$rowZipEmp = mysql_fetch_row($resZipEmp);

				$argElements = array("EntityType"=>'Employee',"EntityId"=>$rowEmpGeoCode[1],"EntityZip"=>$rowZipEmp[0]);	
				//$OBJ_NET_PAYROLL_Geo = new NetPayroll();
				//$rowEmpGeoCode[0] = $OBJ_NET_PAYROLL_Geo->setEntityGeo($argElements);				
			}

			//$OBJ_NET_PAYROLL = new NetPayroll('Assignment','','',$rowEmpGeoCode[0]);
			?>
			<div class="form-back">
			<table width="98%" border="0" class="crmsummary-edit-table" id="crm-joborder-taxsetupDiv-Table">
			<tr>
				<td class="crmsummary-content-title">
					<div id="crm-joborder-taxsetupDiv-plus1" class="DisplayNone"><a style='text-decoration: none;' href="javascript:classToggle('taxsetup','plus')"><span class="crmsummary-content-title">Tax Setup</span></a></div>
					<div id="crm-joborder-taxsetupDiv-minus1"  ><a style='text-decoration: none;' href="javascript:classToggle('taxsetup','minus')"><span class="crmsummary-content-title">Tax Setup</span></a></div>
				</td>
				<td>
					<span id="rightflt" <?php echo $rightflt;?>>
					<span class="summaryform-bold-close-title" id="crm-joborder-taxsetupDiv-close" style="width:auto;">
					<a style='text-decoration: none;' onClick="classToggle('taxsetup','minus')"  href="#crm-joborder-taxsetupDiv-plus">close</a>
					</span>
					<span class="summaryform-bold-close-title" id="crm-joborder-taxsetupDiv-open" style="display:none;width:auto;">
					<a style='text-decoration: none;' onClick="classToggle('taxsetup','plus')"  href="#crm-joborder-taxsetupDiv-minus"> open</a>
					</span>
					<div class="form-opcl-btnleftside"><div align="left"></div></div>
					<div id="crm-joborder-taxsetupDiv-plus" class="DisplayNone" style="width:auto;"><a onClick="classToggle('taxsetup','plus')" class="form-op-txtlnk" href="#crm-joborder-taxsetupDiv-minus"><b>+</b></a></div>
					<div id="crm-joborder-taxsetupDiv-minus"><a onClick="classToggle('taxsetup','minus')" class="form-cl-txtlnk" href="#crm-joborder-taxsetupDiv-plus"><b>-</b></a></div>
					<div class="form-opcl-btnrightside"><div align="left"></div></div>
					</span>
				</td>
			</tr>
			</table>
			</div>

			<div class="jocomp-back" id="crm-joborder-taxsetupDiv" name="crm-joborder-taxsetupDiv" <?php echo $style_table;?>>
			<table width="98%" border="0" class="crmsummary-jocomp-table">		
			<tr>
				<td>
					<?php
					$chkArray = array();
					$chkExArray = array();
					$tempArr = explode('|',$selectedTaxes);
					foreach($tempArr as $val)
					{
						$arr = explode("^",$val);
						$chkArray[$arr[0]] = $arr[1];
						if($arr[2]=='Y')
							array_push($chkExArray,$arr[0]);
					}
					//echo $OBJ_NET_PAYROLL->getTaxes('Assignment',$company_fetch[11],$chkArray,$chkExArray);?>
				</td>
			</tr>
			</table>
			</div>
			<?php
		}
		?>		

		<div class="form-back">
		<table width="98%" border="0" class="crmsummary-edit-table" id="crm-joborder-hrprocessDiv-Table">
		<tr>
			<td width="167" class="crmsummary-content-title">
				<div id="crm-joborder-hrprocessDiv-plus1" class="DisplayNone"><a style='text-decoration: none;' href="javascript:classToggle('hrprocess','plus')"><span class="crmsummary-content-title">Hiring Process</span></a></div>
				<div id="crm-joborder-hrprocessDiv-minus1"  ><a style='text-decoration: none;' href="javascript:classToggle('hrprocess','minus')"><span class="crmsummary-content-title">Hiring Process</span></a></div>
			</td>
			<td>
				<span id="rightflt" <?php echo $rightflt;?>>
				<span class="summaryform-bold-close-title" id="crm-joborder-hrprocessDiv-close" style="width:auto;">
				<a style='text-decoration: none;' onClick="classToggle('hrprocess','minus')"  href="#crm-joborder-hrprocessDiv-plus">close</a>
				</span>
				<span class="summaryform-bold-close-title" id="crm-joborder-hrprocessDiv-open" style="display:none;width:auto;">
				<a style='text-decoration: none;' onClick="classToggle('hrprocess','plus')"  href="#crm-joborder-hrprocessDiv-minus"> open</a>
				</span>
				<div class="form-opcl-btnleftside"><div align="left"></div></div>
				<div id="crm-joborder-hrprocessDiv-plus" class="DisplayNone" style="width:auto;"><a onClick="classToggle('hrprocess','plus')" class="form-op-txtlnk" href="#crm-joborder-hrprocessDiv-minus"><b>+</b></a></div>
				<div id="crm-joborder-hrprocessDiv-minus"><a onClick="classToggle('hrprocess','minus')" class="form-cl-txtlnk" href="#crm-joborder-hrprocessDiv-plus"><b>-</b></a></div>
				<div class="form-opcl-btnrightside"><div align="left"></div></div>
				</span>
			</td>
		</tr>
		</table>
		</div>

		<div class="jocomp-back" id="crm-joborder-hrprocessDiv" name="crm-joborder-hrprocessDiv" <?php echo $style_table;?>>
		<table width="98%" border="0" class="crmsummary-jocomp-table">		
		<tr>
			<td width="167" class="summaryform-bold-title">Contact Method</td>
			<td>
				<span class="summaryform-formelement">
				<input name="hpcmphone" type="checkbox" id="hpcmphone" value="phone" <?php echo sent_check($hirepro[0],'phone');?>></span>
				<span class="summaryform-bold-title">Phone</span>
				<span class="summaryform-formelement"><input name="hpcmmobile" type="checkbox" id="hpcmmobile" value="Mobile" <?php echo sent_check($hirepro[1],'Mobile');?>>
				</span><span class="summaryform-bold-title">Mobile</span>
				<span class="summaryform-formelement"><input name="hpcmfax" type="checkbox" id="hpcmfax" value="Fax" <?php echo sent_check($hirepro[2],'Fax');?>>
				</span><span class="summaryform-bold-title">Fax</span>
				<span class="summaryform-formelement"><input name="hpcmemail" type="checkbox" id="hpcmemail" value="Email" <?php echo sent_check($hirepro[3],'Email');?>>
				</span><span class="summaryform-bold-title">Email</span>
			</td>
		</tr>
		<tr>
			<td valign="top"><span class="summaryform-bold-title">Requirements</span></td>
			<td>
				<span class="summaryform-formelement"><input name="hrresume" type="checkbox" id="hrresume" value="Resume" <?php echo sent_check($hirepro[4],'Resume');?>></span>
				<span class="summaryform-bold-title">Resume </span>
				<span class="summaryform-formelement"><input name="pinterview" type="checkbox" id="pinterview" value="Pinterview" <?php echo sent_check($hirepro[5],'Pinterview');?>></span>
				<span class="summaryform-bold-title">Phone Interview </span>			
				<span class="summaryform-formelement"><input name="interview" type="checkbox" id="interview" value="Interview" <?php echo sent_check($hirepro[6],'Interview');?>>
				</span>
				<span class="summaryform-bold-title">Interview</span><span class="summaryform-formelement">(avg #<input name="hpraverage" type="text" id="hpraverage" value="<?php echo html_tls_specialchars($elements[47],ENT_QUOTES);?>" size=15 maxlength="2">)</span>
				<br>
				<span class="summaryform-formelement"><input name="backgr" type="checkbox" id="backgr" value="Bcheck" <?php echo sent_check($hirepro[7],'Bcheck');?>>
				</span><span class="summaryform-bold-title">Background Check</span>
				<span class="summaryform-formelement"><input name="drug" type="checkbox" id="drug" value="Dscreen" <?php echo sent_check($hirepro[8],'Dscreen');?>>
				</span><span class="summaryform-bold-title">Drug Screen</span>
				<span class="summaryform-formelement"><input name="physical" type="checkbox" id="physical" value="Physical" <?php echo sent_check($hirepro[9],'Physical');?>>
				</span><span class="summaryform-bold-title">Physical</span>
				<span class="summaryform-formelement"><input name="govt" type="checkbox" id="govt" value="Gclearance" <?php echo sent_check($hirepro[10],'Gclearance');?>>
				</span><span class="summaryform-bold-title">Govt Clearance</span><br>
				<span class="summaryform-formelement"><input name="addcheck" type="checkbox" id="addcheck" value="Addinfo" <?php echo sent_check($hirepro[11],'Addinfo');?>>
				</span><span class="summaryform-bold-title">Additional Info </span> 
				<input name="addinfo" type=text class="summaryform-formelement" id="addinfo" value="<?php echo html_tls_specialchars($elements[46],ENT_QUOTES);?>"  size=50 maxsize=100>
			</td>
		</tr>			
		</table>
		</div>
		</div>

		<div class="form-back">
		<table width="98%" border="0" class="crmsummary-edit-table">
		<tr>
			<td width="167" valign="top" class="crmsummary-content-title">Notes</td>
			<td id="assignNotes"><textarea rows="5" cols="60" name="notes" id="notes"><?php echo html_tls_specialchars($elements[45],ENT_QUOTES);?></textarea></td>
		</tr>
		</table>
		</div>
		
		<?php
		if(($mode != "newassign") && trim($modfrom) != "hiring" && $copyasign !="yes")
		{
		?>
		<div class="form-back">
			<table width="98%" border="0" class="crmsummary-edit-table" id="crm-joborder-historicalRatesDiv-Table">
			<tr>
				<td width="" class="crmsummary-content-title">
					<div id="crm-joborder-historicalRatesDiv-plus1" class="DisplayNone"><a style='text-decoration: none;' href="javascript:classToggle('historicalRates','plus')"><span class="crmsummary-content-title">History</span></a></div>
					<div id="crm-joborder-historicalRatesDiv-minus1"  ><a style='text-decoration: none;' href="javascript:classToggle('historicalRates','minus')"><span class="crmsummary-content-title">History</span></a></div>
				</td>
				<td>
					<span id="rightflt" <?php echo $rightflt;?>>
					<span class="summaryform-bold-close-title" id="crm-joborder-historicalRatesDiv-close" style="width:auto;">
					<a style='text-decoration: none;' onClick="classToggle('historicalRates','minus')"  href="#crm-joborder-historicalRatesDiv-plus">close</a>
					</span>
					<span class="summaryform-bold-close-title" id="crm-joborder-historicalRatesDiv-open" style="display:none;width:auto;">
					<a style='text-decoration: none;' onClick="classToggle('historicalRates','plus')"  href="#crm-joborder-historicalRatesDiv-minus"> open</a>
					</span>
					<div class="form-opcl-btnleftside"><div align="left"></div></div>
					<div id="crm-joborder-historicalRatesDiv-plus" class="DisplayNone" style="width:auto;"><a onClick="classToggle('historicalRates','plus')" class="form-op-txtlnk" href="#crm-joborder-historicalRatesDiv-minus"><b>+</b></a></div>
					<div id="crm-joborder-historicalRatesDiv-minus"><a onClick="classToggle('historicalRates','minus')" class="form-cl-txtlnk" href="#crm-joborder-historicalRatesDiv-plus"><b>-</b></a></div>
					<div class="form-opcl-btnrightside"><div align="left"></div></div>
					</span>
				</td>
			</tr>
			<tr>
						<td colspan=4>
							<span class="commRolesNoteStyle">
							Note: History data is not displayed for Shift(s)/Scheduling.
							</span>
						</td>
				  </tr>	
			</table>
		</div>
		   
		    <div class="jocomp-back" id="crm-joborder-historicalRatesDiv" name="crm-joborder-historicalRatesDiv" <?php echo $style_table;?>>
			    <!--<table width="98%" border="0" class="crmsummary-jocomp-table">		
				<tr>
					<td width="167" class="summaryform-bold-title"></td>
					<td>
						<span class="summaryform-formelement">
							Rates history not available.
						</span>
					</td>
				</tr>
			    </table>-->
			    <table width="100%" cellspacing="0" cellpadding="5" border="0" align="left" style="padding-left:0px;">
				<tbody>
					<tr class="summaryform-formelement">						
						<td>
							<?php
								displayRatesHistory($showAssignid);
							?>
							
						</td>
					</tr>
				</tbody>
			    </table>
		    </div>
		<?php } ?> 

		<?php
		if($modfrom=="employee")
		{
			?>
			<div class="form-back">
			<table width="98%" border="0" class="crmsummary-edit-table">
			<tr>
				<td><img src=/BSOS/images/white.jpg width=10 height=10></td>
			</tr>
			<tr>
				<td class="crmsummary-content-title">Closed/Cancel&nbsp;Assignments&nbsp;</td>
			</tr>
    		<tr>
			<?php
			$page015="";

			$cque="select ".tzRetQueryStringDTime('udate','DateTimeSec','/').",client,project,".tzRetQueryStringSTRTODate('s_date','%m-%d-%Y','Date','/')." ,".tzRetQueryStringSTRTODate('e_date','%m-%d-%Y','Date','/')." ,jtype, sno,ustatus from hrcon_jobs where username='".$conusername."' and (ustatus='closed' or ustatus='cancel') order by udate desc";
			$cres=mysql_query($cque,$db);
			if(mysql_num_rows($cres)==0)
			{
				print "<tr><td><table width=100% border=0 cellpadding=2 cellspacing=0><tr class=hthbgcolor><td width=20%><font class=afontstyle>Date</font></td><td width=20%><font class=afontstyle>Assignment&nbsp;Type</font></td><td width=20%><font class=afontstyle>Assignment&nbsp;Name</font></td><td width=20%><font class=afontstyle>Start Date</font></td><td width=20%><font class=afontstyle>End Date</font></td></tr>";
				print "<tr><td colspan='5'><font class=afontstyle>No closed/cancel assignments</font></td></tr></table>";
			}
			else
			{
				while($crow=mysql_fetch_row($cres))
				{
					if($page015=="")
						$page015=$crow[0]."|".$crow[1]."|".$crow[2]."|".$crow[3]."|".$crow[4]."|".$crow[5]."|".$crow[6]."|".$crow[7];
					else
						$page015.="^".$crow[0]."|".$crow[1]."|".$crow[2]."|".$crow[3]."|".$crow[4]."|".$crow[5]."|".$crow[6]."|".$crow[7];
				}
			}

    	    $fdata11=array();
        	if($page015!="")
	        {
    	    	$tok1=explode("^",$page015);
        		for($i=0;$i<count($tok1);$i++)
	        		$fdata11[$i]=explode("|",$tok1[$i]);
        	}

			if(count($fdata11)>0)
			{
				print "<tr><td><table width=100% border=0 cellpadding=2 cellspacing=0><tr class=hthbgcolor><td width=20%><font class=afontstyle>Date</font></td><td width=20%><font class=afontstyle>Assignment&nbsp;Type</font></td><td width=20%><font class=afontstyle>Assignment&nbsp;Name</font></td><td width=20%><font class=afontstyle>Start Date</font></td><td width=20%><font class=afontstyle>End Date</font></td></tr>";
				for($j=0;$j<count($fdata11);$j++)
				{
					$st_exp =explode("/",$fdata11[$j][3]); 
					$st_mon = $st_exp[0];
					$st_day = $st_exp[1];
					$st_year = $st_exp[2];

					//displaying blank when no date entered by user
					if(($fdata11[$j][3]=='00/00/0000') || ($st_mon == '00') || ($st_year == '0000') || ($st_day == '00')|| ($st_mon == '00' && $st_day == '00'))
						$fdata11[$j][3]='';

					$end_exp =explode("/",$fdata11[$j][4]); 
					$end_mon = $end_exp[0];
					$end_day = $end_exp[1];
					$end_year = $end_exp[2];

					if(($fdata11[$j][4]=='00/00/0000')  || ($end_mon == '00') || ($end_year == '0000') || ($end_day == '00') || ($end_mon == '00' && $end_day == '00'))
						$fdata11[$j][4]='';

					if($j%2==0)
						$class="tr1bgcolor";
					else
						$class="tr2bgcolor";
					print "<tr class=".$class.">";
					?>
					<td><font class=afontstyle><a href="javascript:showJobs('<?php echo $fdata11[$j][6];?>','<?php echo $fdata11[$j][7];?>')"><?php echo $fdata11[$j][0];?></a></font></td>
					<?php
					if($fdata11[$j][5]=="OB")
					{
						if($fdata11[$j][5]=="OB")
							$status="On Bench";
						print "<td colspan=4><font class=afontstyle>".$status."</font></td>";
					}
					else
					{
						$status="Project";
						print "<td><font class=afontstyle>".$status."</font></td>";
						$fdata11[$j][2]="Project";

						for($i=2;$i<5;$i++)
							print "<td><font class=afontstyle>".$fdata11[$j][$i]."</font></td>";
					}
					print "</tr>";
				}
				print "</table></td></tr>";
			}
			?>
		</tr>
		</table>

		<!-- Added 'Needs Approval' assignments to dispay without editable -- kumar raju k.-->
		<table width="100%">
		<tr>
			<td><img src=/BSOS/images/white.jpg width=10 heigh=10></td>
		</tr>
		<tr>
			<td class="crmsummary-content-title">Need&nbsp;Approval&nbsp;Assignments&nbsp;</td>
		</tr>
		<tr>
		<?php
		$page015="";

		$cque="SELECT emp.client,manage.name,emp.project,".tzRetQueryStringSTRTODate('emp.s_date','%m-%d-%Y','Date','/')." ,".tzRetQueryStringSTRTODate('emp.e_date','%m-%d-%Y','Date','/')." ,emp.jtype,emp.sno,emp.ustatus FROM hrcon_jobs as emp LEFT JOIN manage as manage ON (manage.type = 'jotype' AND manage.sno = emp.jotype) WHERE emp.username='".$conusername."' AND emp.ustatus='pending' AND emp.jtype!='' AND emp.jotype!='0' ORDER BY emp.mdate desc";
		$cres=mysql_query($cque,$db);
		if(mysql_num_rows($cres)==0)
		{
			print "<tr><td><table width=100% border=0 cellpadding=2 cellspacing=0><tr class=hthbgcolor><td width=20%><font class=afontstyle>Assignment&nbsp;Type</font></td><td width=20%><font class=afontstyle>Job&nbsp;Type</font></td><td width=20%><font class=afontstyle>Assignment&nbsp;Name</font></td><td width=20%><font class=afontstyle>Start Date</font></td><td width=20%><font class=afontstyle>End Date</font></td></tr>";
			print "<tr><td colspan='5'><font class=afontstyle>No pending assignments to approve</font></td></tr></table>";
		}
		else
		{
			while($crow=mysql_fetch_row($cres))
			{
				if($page015=="")
					$page015=$crow[0]."|".$crow[1]."|".$crow[2]."|".$crow[3]."|".$crow[4]."|".$crow[5]."|".$crow[6]."|".$crow[7]."|".$crow[8];
				else
					$page015.="^".$crow[0]."|".$crow[1]."|".$crow[2]."|".$crow[3]."|".$crow[4]."|".$crow[5]."|".$crow[6]."|".$crow[7]."|".$crow[8];
			}
		}

		$fdata11=array();
		if($page015!="")
		{
			$tok1=explode("^",$page015);
			for($i=0;$i<count($tok1);$i++)
				$fdata11[$i]=explode("|",$tok1[$i]);
		}

		if(count($fdata11)>0)
		{
			print "<tr><td><table width=100% border=0 cellpadding=2 cellspacing=0><tr class=hthbgcolor><td width=20%><font class=afontstyle>Assignment&nbsp;Type</font></td><td width=20%><font class=afontstyle>Job&nbsp;Type</font></td><td width=20%><font class=afontstyle>Assignment&nbsp;Name</font></td><td width=20%><font class=afontstyle>Start Date</font></td><td width=20%><font class=afontstyle>End Date</font></td></tr>";
			for($j=0;$j<count($fdata11);$j++)
			{
				$st_exp =explode("/",$fdata11[$j][3]); 
				$st_mon = $st_exp[0];
				$st_day = $st_exp[1];
				$st_year = $st_exp[2];

				//displaying blank when no date entered by user
				if(($fdata11[$j][3]=='00/00/0000') || ($st_mon == '00') || ($st_year == '0000') || ($st_day == '00')|| ($st_mon == '00' && $st_day == '00'))
					$fdata11[$j][3]='';

				$end_exp =explode("/",$fdata11[$j][4]); 
				$end_mon = $end_exp[0];
				$end_day = $end_exp[1];
				$end_year = $end_exp[2];

				if(($fdata11[$j][4]=='00/00/0000')  || ($end_mon == '00') || ($end_year == '0000') || ($end_day == '00') || ($end_mon == '00' && $end_day == '00'))
					$fdata11[$j][4]='';

				if($j%2==0)
					$class="tr1bgcolor";
				else
					$class="tr2bgcolor";
				print "<tr class=".$class.">";

				if($fdata11[$j][5]=="OB")
					$status="On Bench";
				else	
					$status="Project";

				print "<td><font class=afontstyle>".$status."</font></td>";

				if($fdata11[$j][2]=="")
					$fdata11[$j][2]="Project";

				for($i=1;$i<5;$i++)
					print "<td><font class=afontstyle>".$fdata11[$j][$i]."</font></td>";

				print "</tr>";
			}
			print "</table></td></tr>";
		}
		?>
		</tr>
		</table>
		</div>
		<?
	}
	?>
	</fieldset>
	</td>
	</tr>
	</table>
	</td>
	</tr>
	
	<tr>
		<td><img src=/BSOS/images/white.jpg width=10 heigh=10></td>
	</tr>

	<tr class="NewGridBotBg">
	<?php
	if($modfrom=="hiring")
	{
		if($assign=="edit")
		{
			$StatusType="update";
			$name=explode("|","fa fa-clone~Update|fa fa-times~Close");
			$link=explode("|","javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		}
		else if($assign=="new")
		{
			$StatusType="new";
			$name=explode("|","fa-file~Add|fa fa-times~Close");
			$link=explode("|","javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		}
		else
		{
			$name=explode("|","fa fa-thumbs-o-up~Hire|fa fa-hand-o-left~Back|fa fa-hand-o-right~Next");
			$link=explode("|","javascript:doHire(15)|javascript:validate(14,15)|javascript:validate(22,15)");
		}
		$heading="user.gif~Hiring&nbsp;Management";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="empman")
	{
		if($assign=="edit")
			$name=explode("|","fa-arrow-circle-up~Export|fa fa-clone~Update|fa fa-times~Close");
		else
			$name=explode("|","fa-file~Add|fa fa-times~Close");

		if($assign=="edit")
			$link=explode("|","javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		else
			$link=explode("|","javascript:doSPage15('OP','$StatusType')|javascript:window.close()");
		$heading="user.gif~Employee&nbsp;Management";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="approve")
	{
		$name=explode("|","fa-arrow-circle-up~Export|fa-floppy-o~Save|fa fa-check-square-o~Approve|fa-ban~Cancelled|fa fa-times~Close");
		$link=explode("|","javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doApprove(15,this,'approve','save')|javascript:doApprove(15,this,'approve')|javascript:doApprove('cancelled',this,'cancel')|javascript:closeAssignmentWindow()");
		$heading="user.gif~Assignments";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="updateasgmt" && $copyasign !="yes")  // Accounting update
	{
		$name=explode("|","fa-files-o~Copy|fa-arrow-circle-up~Export|fa fa-clone~Update|fa fa-times~Close");
		$link=explode("|","javascript:doCopyPage();|javascript:Exportpopup('$rec_sno','$rec_status','$rec_tblname')|javascript:doApprove(15,this,'update')|javascript:closeAssignmentWindow()");
		$heading="user.gif~Assignments";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($copyasign =="yes")  // Copy Assigmnent 
	{
		$name=explode("|","a fa-floppy-o~Save|fa fa-times~Close");
		$link=explode("|","javascript:doAsgPage15('OP');|javascript:window.close()");
		$heading="user.gif~Assignments";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	else if($modfrom=="newasgmt")
	{
		$name=explode("|","fa-file~Add|fa fa-times~Close");
		$link=explode("|","javascript:doAsgPage15('OP');|javascript:window.close()");
		$heading="user.gif~New&nbsp;Assignment";
		$menu->showHeadingStrip1($name,$link,$heading);
	}
	?>
	</tr>
	<tr>
		<td><img src=/BSOS/images/white.jpg width=10 heigh=10></td>
	</tr>
	</table>
	</div>
	</td>
</tr>
</table>
<!-- Re-Assign Model Box Start -->
<input type="hidden" name="re_username" id="re_username" value=""/>
<?php
if($modfrom !="newasgmt" && $copyasign !="yes") {
?>
<input type="hidden" name="empname" id="empname" value=""/>
<input type="hidden" name='candidate' id="candidate" value="0">
<?php
}
?>
<input type="hidden" name="re_assign_selectedEmpsno" id="re_assign_selectedEmpsno" value=""/>
<input type="hidden" name="re_assign_selDivIds" id="re_assign_selDivIds" value=""/>
<input type="hidden" name="sm_start_exp_end_date" id="sm_start_exp_end_date" value=""/>
<input type="hidden" name="flag_reassign" id="flag_reassign" value='No'>
<input type="hidden" name="multiratestxt" id="multiratestxt" value=''>
<input type="hidden" name="noshowcheck" id="noshowcheck" value=''>
<input type="hidden" name="noshowdescription" id="noshowdescription" value=''>
<input type="hidden" name="reasoncodesno" id="reasoncodesno" value=''>
<input type="hidden" name='from_username' id="from_username" value="<?php echo $conusername1;?>">
<input type="hidden" name='from_assign_no' id="from_assign_no" value="<?php echo $showAssignid;?>">
<input type="hidden" name='joborder_id' id="joborder_id" value="<?php echo $jobPosVal;?>">

<div id="reassignshiftdialog" title="NoShow/Re-Assign" style="display:none;">						
	<table id="smdatetable" class="ProfileNewUI">
		<tr>
			<td>
				<input type="checkbox" name='noshow' id='noshow' onchange='showReason();' value='Yes'/>
			</td>
			<td>
				<b>No Show<span id="no_show"></span></b>
			</td>
		</tr>
		<tr style="visibility:hidden;" id="reasonBox">
			<td><b>Reason Code
				<span></span></b>
			</td>
			<td>
				<div id="reasoncodebox"></div>
			</td>
		</tr>
		<tr style="visibility:hidden;" id="reasonDescBox">
			<td><b>Reason Description
				<span></span></b>
			</td>
			<td>
				<textarea rows='3' cols='25' name='noshowreason' id='noshowreason' style="resize: none;"></textarea>
			</td>
		</tr>
		<tr>
			<td><b>Re-Assign:
				<span></span></b>
			</td>
			<td colspan='2'>
				<span id='employee-change'><a class="crm-select-link reassigncodedetail" href="javascript:employee_parent_popup()"><strong>Select</strong> Employee</a>&nbsp;<a href="javascript:employee_parent_popup()"><i alt='Search' class='fa fa-search'></i></a></span>
			</td>
		</tr>
	</table>
	<div class="reassignbtnNew"><input type="button" name="reassign" id="reassign" value="Re-Assign" onClick="reassignProcess()"/></div>
</div>
<!-- END -->

<!-- This Model Box is for the Cancelling the selected dates  -->
<input type="hidden" id="cancelshiftselecteddates" name="cancelshiftselecteddates" value="" />
<input type="hidden" id="cancelshiftsmdatetimesel" name="cancelshiftsmdatetimesel" value="" />

<div id="cancelshiftdialog" title="Cancel Shift(s)" style="display:none;">						
	<table id="smdatetable" class="ProfileNewUI">
		
		<tr>
			<td><b>Cancel Code
				<span></span></b>
			</td>
			<td>
				<div id="cancelcodebox"></div>
			</td>
		</tr>
		<tr>
			<td><b>Cancel Description
				<span></span></b>
			</td>
			<td>
				<textarea rows='3' cols='25' name='cancellationreason' id='cancellationreason' style="resize: none;"></textarea>
			</td>
		</tr>
	</table>
	<div class="reassignbtnNew"><input type="button" name="cancelshift" id="cancelshift" value="Cancel Shift" onClick="cancelShiftProcess()"/></div>
</div>
<!-- END -->
 
<?php
if($mode=="editassign" && $chkWithHoldingSno!='')
{
	$sqlWithHolding = "SELECT  IF(nr_cert = 'Y', 'true', 'false'), jur_int_treat, awh, fillsno, pexempt,pamount,sexempt,samount FROM vprt_tax_emp_us_setup WHERE assid='".$showAssignid."' AND status='A' AND taxsno=".$chkWithHoldingSno;	
	$resWithHolding = mysql_query($sqlWithHolding,$db);
	$rowWithHolding = mysql_fetch_row($resWithHolding);
	if(mysql_num_rows($resWithHolding)>0)
	{
		?>
		<script language="javascript">
		try
		{
			document.getElementById("txtLocStateWH").value="<?php echo $rowWithHolding[2];?>";
			document.getElementById("lstLocFilSts").value="<?php echo $rowWithHolding[3];?>";
			document.getElementById("txtLocPexempt").value="<?php echo $rowWithHolding[4];?>";
			document.getElementById("txtLocPamt").value="<?php echo $rowWithHolding[5];?>";
			document.getElementById("txtLocSexempt").value="<?php echo $rowWithHolding[6];?>";
			document.getElementById("txtLocSamt").value="<?php echo $rowWithHolding[7];?>";
			document.getElementById("chkNonResident").checked=<?php echo $rowWithHolding[0];?>;
			document.getElementById("lstJuriIntr").value="<?php echo $rowWithHolding[1];?>";
		}
		catch(e){}
		</script>
		<?php
		}
	}
?>

<input type=hidden name='candtype' id='candtype' value="<?php echo $type;?>">
<script language="javascript">
var rowCount="<?php echo (int)$newRowId+1;?>";
var row_class="<?php echo $row_class;?>";
doAssign();
hideElements1();
setFormObject("document.conreg");
<?php 
//Execute the Js script for the old shift schedule display
if($schedule_display == 'OLD')
{
if($defaultsch!="fulltime")
	echo "displayScheduledata('".$schdet."');";

if( !trim($elements[14]) )
	echo "defultFullTime('document.conreg');";

if($defaultsch=="fulltime")
	echo "defultFullTime('document.conreg');";
}
?>
var place_jobtype = "<?php echo $place_jobtype;?>";
var assignment_rates = "<?php echo $assignment_mulrates;?>";
var assignRates = "";
if(place_jobtype!="Direct" && place_jobtype!="Internal Direct")
{
	if(assignment_rates!="")
	{
		var splitRates = assignment_rates.split('^DefaultRates^');
		var assignRates = splitRates[0];
	}
	<?php /*?>
	editDispMulRates('<?php echo $ratesObj->getMultipleRatesType();?>|RatesValue|'+assignRates);
	<?php */?>
	<?php
	if($assign == 'edit')
	{
	?>
		customRateTypes(<?php echo $conjob_sno; ?>, '<?php echo $moderate_type;?>');
	<?php
	}
	?>
}
else
{
	multipleRatesStr = "<?php echo $ratesObj->getMultipleRatesType();?>";
}
document.getElementById("hdnJobType").value = document.getElementById("jotype").options[document.getElementById("jotype").selectedIndex].value;

function enableTaxLink()
{
}


</script>



<?php
	$selQry = "SELECT ma.sno FROM multiplerates_assignment jo,multiplerates_master ma WHERE jo.status = 'ACTIVE' AND jo.asgnid = '".$conjob_sno."' AND jo.asgn_mode='".$moderate_type."' AND ma.status='ACTIVE' AND ma.default_status='N' AND ma.rateid=jo.ratemasterid GROUP BY ma.sno";

	$rateValuesArray = array();
	
	$resQry = mysql_query($selQry,$db);
	while($recQry = mysql_fetch_assoc($resQry))
	{
		$rateValuesArray[] = $recQry['sno'];
		?>
		<script type="text/javascript">
			selectedprtidsarray.push(<?php echo $recQry['sno'];?>);
		</script>
		<?php
	}
	if(!empty($rateValuesArray))
	{
		?>
		<script type="text/javascript">
			pushAddEditRateRowArray('<?php echo implode(',', $rateValuesArray);?>');
			document.getElementById('selectedcustomratetypeids').value = '<?php echo implode(',', $rateValuesArray);?>';
		</script>
		<?php
	}



if(($jrtcontact>0 || $jrt_loc>0) && ($billcontact>0 || $bill_loc>0))
{
	print "<script>getBothACCLocations('".$jrtcompany."','".$jrtcontact."','".$jrt_loc."','jrt','".$billcompany."','".$billcontact."','".$bill_loc."','bill',1);</script>";
}
else if(($jrtcontact>0 || $jrt_loc>0) && ($billcontact==0 || $billcontact=="" || $bill_loc==0 || $bill_loc==""))
{
	print "<script>getACCLocations('".$jrtcompany."','".$jrtcontact."','".$jrt_loc."','jrt',1);</script>";
}
else if(($billcontact>0 || $bill_loc>0) && ($jrtcontact==0 || $jrtcontact=="" || $jrt_loc==0 || $jrt_loc==""))
{
	print "<script>getACCLocations('".$billcompany."','".$billcontact."','".$bill_loc."','bill',1);</script>";
}

?>
<script type="text/javascript">
    <?php
    if($burden_status == 'yes') {
    ?>
    BTChangeAction(document.getElementById('burdenType'),'assignment',true);
    BillBTChangeAction(document.getElementById('bill_burdenType'),'assignment',true);
    <?php
    } else {
        ?>
        calculatebtmargin();
        <?php
    }
    ?>
</script>
<input type="hidden" value="<?php echo $conjob_sno; ?>" id="assign_id_to_get_custom_rates" />
<input type="hidden" value="<?php echo $moderate_type; ?>" id="moderate_type" />

<input type="hidden" id="cap_separated_custom_rates" value="" />
<script>
var jrt_bill_loc = window.document.getElementById('jrt_loc');
var sbloc = jrt_bill_loc.value.split("-");
if(sbloc[0]=="loc" || sbloc[0]=="com")
{
	<?php
	if($chk_bt)
	{
            if($assign=="new"){
            ?>
			
                preLoadBurdenType(sbloc[1],'HRM');
            <?php
            } else{
                ?>
                preLoadBurdenDropList(sbloc[1],'HRM');     
            <?php
            } 
	}else
	
	
	{?>preLoadBurdenDropList(sbloc[1],'HRM'); <?PHP
		}
	
	?>
}
</script>

<?php
if($customRateIds != "")
{
	$crStr = $elements[96];
	$crStrExp = explode("^^CRSPLIT^^",$crStr);
	$CRFormedArr = array();
	foreach($crStrExp as $crVal)
	{
		if($crVal != "")
		{
			$v = explode("^",$crVal);
			$CRFormedArr[] = array
			(
			"ratetype"=>$v[0],
			"sno"=>$v[1],
			"rate"=>$v[2],
			"period"=>$v[3],
			"currency"=>$v[4],
			"billable"=>$v[5],
			"taxable"=>$v[6]
			);
		}
	}
	$conctStr = buildCustomRates($objMRT,$CRFormedArr);
	?>
	<script>
	document.getElementById("multipleRatesTab").innerHTML  = '<?php echo $conctStr; ?>';
	pushAddEditRateRowArray('<?php echo $customRateIds; ?>');
	var crArr = document.getElementById('selectedcustomratetypeids').value.split(",");
	for(var i = 0; i < crArr.length; i++)
	{
		pushSelectedPayRateidsArray(crArr[i]);
	}

	</script>
	<?php
}
?>
<style type="text/css">
	.select2-container, .select2-drop, .select2-search, .select2-search input {
	width: 250px !important; 
}
</style>
<script type="text/javascript">    
// Shift Color Codes Dropdown script starts here
	$(document).ready(function() {
		  function formatColor(shift){
		  	if(shift.title!='')
		  	{
		  		var $shift = $(
			  '<span style="background-color: '+shift.title+'" class="color-label"></span><span>' + shift.text + '</span>'
				);
				return $shift;
		  	}
		  	else
		  	{
		  		var $shift = $('<span>' + shift.text + '</span>');
				return $shift;
		  	}
			
		  };
		 
		/* $('#new_shift_name').select2().on("change", function(e) {
          width: "150px",
			placeholder: "Select Shift",
			minimumResultsForSearch: -1,
			templateResult: formatColor,
			templateSelection: formatColor
        });*/

		  $('#new_shift_name').select2({
			width: "150px",
			placeholder: "Select Shift",
			minimumResultsForSearch: -1,
			templateResult: formatColor,
			templateSelection: formatColor
		  });
	 });
	// Shift Color Codes Dropdown script ends here   
	$(document).ready(function(){
		document.getElementById("jrt_loc").style.visibility = "visible";
		$("#jrt_loc").select2();
	});
	
	function OpenHistory(hisdate,his_sno)
	{
		var v_heigth = 700;
		var v_width  = 1025;

		remote=window.open("/include/openhisrates.php?modfrom=<?php echo $modfrom; ?>&recno=<?php echo $conjob_sno?>|15|active|16&assign=edit&test_acc=1&hisdate="+hisdate+"&his_sno="+his_sno+"&assgnid=<?php echo $showAssignid?>&hrsno=<?php echo $conjob_sno?>&assignstatus=<?php echo $assignmentStatus?>&ACC_AS_SESSIONRN=<?php echo $_SESSION['ACC_AS_SESSIONRN'];?>","NewAssignment","width="+v_width+"px,height="+v_heigth+"px,statusbar=no,menubar=no,scrollbars=yes,left=50,top=50,dependent=yes");
		remote.focus();
	}

</script>
<script>
$(window).focus(function() {
    if(document.getElementById('payrate_calculate_confirmation_window_onblur').value != ""){
        document.getElementById('payrate_calculate_confirmation').value = document.getElementById('payrate_calculate_confirmation_window_onblur').value;
    }
    if(document.getElementById('billrate_calculate_confirmation_window_onblur').value != ""){
        document.getElementById('billrate_calculate_confirmation').value = document.getElementById('billrate_calculate_confirmation_window_onblur').value;
    }
});
function showReason(){
	
	if(document.getElementById('noshow').checked) {
    	document.getElementById('reasonBox').style.visibility = "visible";
    	document.getElementById('reasonDescBox').style.visibility = "visible";
	} else {
    	document.getElementById('reasonBox').style.visibility = "hidden"; 
    	document.getElementById('reasonDescBox').style.visibility = "hidden";
	}
	getAllreasonCode('reassign');
	return;
}

function getAllreasonCode(type){
	$.ajax({
	    url: '/BSOS/Admin/Shift_Mngmt/saveReasonCode.php?getAllReasonCodes='+type,
	    type: 'POST',
	    async: false,
	    success: function (data) 
	    {	if (type == 'reassign') {
	    		document.getElementById('reasoncodebox').innerHTML=data;
	    	}
	    	if (type == 'cancelshift') {
	    		document.getElementById('cancelcodebox').innerHTML=data;
	    	}
	    }
	});
}

function getReasonCodeDesc(type){

	if (type == 'reassign') {
		var selectreason = document.getElementById("reasoncode");
	}
	if (type == 'cancelshift') {
		var selectreason = document.getElementById("cancelshiftcode");
	}
	var sno = selectreason.options[selectreason.selectedIndex].value;
	if (sno !="") {
		$.ajax({
		    url: '/BSOS/Admin/Shift_Mngmt/saveReasonCode.php?getReasonCodeDesc='+sno,
		    type: 'POST',
		    async: false,
		    success: function (data) 
		    {
		    	if (type == 'reassign') {
	    			document.getElementById('noshowreason').innerHTML=data;
			    	document.getElementById('reasonDescBox').style.visibility = "visible";
		    	}
		    	if (type == 'cancelshift') {
		    		document.getElementById('cancellationreason').innerHTML=data;
		    	}
			    	
			}
		});
	}
	if (sno =="") {
		document.getElementById('noshowreason').innerHTML="";
	};	
}
//function to handle the error notifications for all rates.
$(document).on('keypress', "input[type='text']",function (e) {
	
	if ($(this).hasClass("RateErrorInput")) {
		var key;
		var isCtrl = false;
		var keychar;
		var regExp;
		var flag = false;
	
		if(window.event) {
				key = e.keyCode;
				isCtrl = window.event.ctrlKey
		}
		else if(e.which) {
				key = e.which;
				isCtrl = e.ctrlKey;
		}
		if (isNaN(key)){
			flag = true;
		}
		keychar = String.fromCharCode(key);
		// check for backspace or delete, or if Ctrl was pressed
		if (key == 8 || isCtrl)
		{
			flag = true;
		}
		regExp = /\d/;			
		if (regExp.test(keychar) || flag==true) {
			$(this).removeClass("RateErrorInput");
		}		
	}
});

$(document).on("change", ".BillableRates" , function() {
	var chkBillable = $(this).val();
	if (chkBillable == "N") {
		if ($(this).attr('id')=="payrateBillOpt") {
			if ($("#comm_payrate").hasClass("RateErrorInput")) {
				$("#comm_payrate").removeClass("RateErrorInput")
			}
			if ($("#comm_billrate").hasClass("RateErrorInput")) {
				$("#comm_billrate").removeClass("RateErrorInput")
			}			
		}
		else
		if ($(this).attr('id')=="OvpayrateBillOpt") {
			if ($("#otrate_pay").hasClass("RateErrorInput")) {
				$("#otrate_pay").removeClass("RateErrorInput")
			}
			if ($("#otrate_bill").hasClass("RateErrorInput")) {
				$("#otrate_bill").removeClass("RateErrorInput")
			}	
		}
		else
		if ($(this).attr('id')=="DbpayrateBillOpt") {
			if ($("#db_time_pay").hasClass("RateErrorInput")) {
				$("#db_time_pay").removeClass("RateErrorInput")
			}
			if ($("#db_time_bill").hasClass("RateErrorInput")) {
				$("#db_time_bill").removeClass("RateErrorInput")
			}				
		}
		else{
			var custRateId = $(this).attr('id');
			var custRateIndx = custRateId.substring(12,custRateId.length);
			var custPayRateName = "mulpayRateTxt"+custRateIndx;
			var custBillRateName = "mulbillRateTxt"+custRateIndx;			
			$("input[name='"+custPayRateName+"'").removeClass("RateErrorInput");
			$("input[name='"+custBillRateName+"'").removeClass("RateErrorInput");
		}
	}
});
</script>
</body>
</html>
