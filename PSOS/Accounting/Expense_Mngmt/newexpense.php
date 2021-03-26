<?php
	require("global.inc");
	require_once($akken_psos_include_path.'commonfuns.inc');

	require("Menu.inc");
	require_once('timesheet/class.Timesheet.php'); 
	$menu=new EmpMenu();
	
    	require($akken_psos_include_path."ExpenseRatesClass.php");
    	$timesheetObj 	= new AkkenTimesheet($db);
	
	$que="select type, status, usertype from users where username='".$username."'";
	$res=mysql_query($que,$db);
	$uType = mysql_fetch_object($res);

	// $efrom1 is used for sending emails. DO NOT USE for any other purpose -- Sandhya
	$efrom1 = getFromEmailID($username);
    
	$query="select name from emp_list where username='".$username."'";
	$res=mysql_query($query,$db);
	$myrow=mysql_fetch_row($res);
	$name=$myrow[0];
     
	$aryClasses = getClassesSetups(); // Getting all classes into array.
	$decimalPref    = getDecimalPreference(); 
    function sel($a,$b)
	{
		if($a==$b)
			return "selected";
		else
			return "";
	}

    function sele($a,$b)
	{
		if($a==$b)
			return "checked";
		else
			return "";
    }

    if($empnames=="")
        $new_user=$username;
    else
        $new_user=$empnames;


	$defaultExpenseClass = new defaultRatesDetails();
	if($servicedate!="" && $servicedateto!="")
	{
		$expense_date_arr 	= $defaultExpenseClass->GetDays($servicedate, $servicedateto);
		$thisday1			= strtotime($servicedate);
		$thisday2			= strtotime($servicedateto);
	}
	else
	{
		$expense_date_arr	= $defaultExpenseClass->getWeekdays($date);
		$expense_start_date	= explode(" ", $expense_date_arr[0]);
		$expense_end_date	= explode(" ", $expense_date_arr[6]);
		$servicedate		= $expense_start_date[0];
		$servicedateto		= $expense_end_date[0];
		$thisday1			= strtotime($servicedate);
		$thisday2			= strtotime($servicedateto);
	}
	$today1 = $thisday1;
    $today2 = $thisday2;
    $start_date = date("Y-m-d",$thisday1);
	$end_date = date("Y-m-d",$thisday2);

	$currentdate=date("Y-m-d",getTimeStampByNDays("-1",$thisday1));

	/* check the condition for login user Assignment -vipin 16/12/2008 */	

	$employees = $timesheetObj->getEmployees('Accounting', $username, $start_date, $end_date);	
	if($empnames == '')
	{
		$empnames = $timesheetObj->new_first_user;
	}
	else{
		$empnames = $_POST['empnames'];
	}
	$uname = $_POST['empnames'];
	$emp_uname = ($uname == '')?$empnames:$uname;
	$new_user = ($uname== '')?$empnames:$uname;
	$query="select name,sno from emp_list where username='".$new_user."'";
	$res=mysql_query($query,$db);
	$myrow=mysql_fetch_row($res);
	
	$ename = $myrow[1].'-'.$myrow[0];
	$getmaxdate = "SELECT MAX(edate) FROM par_expense WHERE username='".$new_user."'";
	$maxres=mysql_query($getmaxdate,$db);
	$maxdaterow=mysql_fetch_row($maxres);
		
	$sql_LJS = "SELECT pusername,client,classid FROM hrcon_jobs WHERE username = '".$new_user."' AND pusername!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$end_date."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$start_date."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$start_date."'),1))) AND hrcon_jobs.jtype!='' ORDER BY ustatus,udate desc limit 1";

	$ds_LJS  = mysql_query($sql_LJS, $db);
	$rs_LJS  = mysql_fetch_row($ds_LJS);
	
	$nStatusValue = $rs_LJS[0];
		
    if(!isset($addtype) || $addtype == "")
	{
		$addexp=1;
		$oldvalue="";
		$totalexp=0;
		$advance=0;
		$remarks="";
		$trip="";
		$elements[$addexp-1][0]="";
		$elements[$addexp-1][1]=$nStatusValue;
		$elements[$addexp-1][2]="";
		$elements[$addexp-1][3]="";
		$elements[$addexp-1][4]="";
		$elements[$addexp-1][5]="";
		$elements[$addexp-1][6]="";
		$elements[$addexp-1][7]="";
		$elements[$addexp-1][8]="";
		$elements[$addexp-1][9]=$rs_LJS[2];
		$elements[$addexp-1][10]=$rs_LJS[1];
		$elements[$addexp-1][11] = '';
		$elements[$addexp-1][12] = '';
		if(getStatus('Payable') == 'Yes')
		{
			$elements[$addexp-1][11] = 'pay';
		}
		if(getStatus('Billable') == 'Yes')
		{
			$elements[$addexp-1][12] = 'bil';
		}
	}
	elseif($addtype=="expense")
	{
		if($val == 'add')
			$addexp=$addexp+1;
		
		$totalexp=0;
		$sintime=explode("^",html_tls_specialchars($oldvalue));
		for($i=0;$i<count($sintime);$i++)
		{
			$elements[$i]=explode("|",$sintime[$i]);
			$totalexp=$totalexp+($elements[$i][3] * $elements[$i][4]);
		}

        $elements[$addexp-1][0]="";
		$elements[$addexp-1][1]=$nStatusValue;
		$elements[$addexp-1][2]="";
		$elements[$addexp-1][3]="";
		$elements[$addexp-1][4]="";
		$elements[$addexp-1][5]="";
		$elements[$addexp-1][6]="";
		$elements[$addexp-1][7]="";
		$elements[$addexp-1][8]="";
		$elements[$addexp-1][9]=$rs_LJS[2];
		$elements[$addexp-1][10]=$rs_LJS[1];
		$elements[$addexp-1][11] = '';
		$elements[$addexp-1][12] = '';
		if(getStatus('Payable') == 'Yes')
		{
			$elements[$addexp-1][11] = 'pay';
		}
		if(getStatus('Billable') == 'Yes')
		{
			$elements[$addexp-1][12] = 'bil';
		}
	}
	elseif($addtype=="updateAssign")
	{
		$addexp=$addexp;
		$totalexp=0;
		$sintime=explode("^",html_tls_specialchars($oldvalue));
		for($i=0;$i<count($sintime);$i++)
		{
			$elements[$i]=explode("|",$sintime[$i]);
			$totalexp=$totalexp+($elements[$i][3] * $elements[$i][4]);
		}
		$elements[$addexp][0]="";
		$elements[$addexp][1]=$nStatusValue;
		$elements[$addexp][2]="";
		$elements[$addexp][3]="";
		$elements[$addexp][4]="";
		$elements[$addexp][5]="";
		$elements[$addexp][6]="";
		$elements[$addexp][7]="";
		$elements[$addexp][8]="";
		$elements[$addexp][9]=$rs_LJS[2];
		$elements[$addexp-1][10]=$rs_LJS[1];
	}
	elseif($addtype=="emp")
	{
		$addexp=$addexp;
		$totalexp=0;
		$advance=0;
		$sintime=explode("^",html_tls_specialchars($oldvalue));
		for($i=0;$i<count($sintime);$i++)
		{
			$elements[$i]=explode("|",$sintime[$i]);
			$totalexp=$totalexp+($elements[$i][3] * $elements[$i][4]);
			$advance=$advance+$elements[$i][7];
		}
	}
	elseif($addtype=="delete")
	{
		$addexp=$addexp;
		$sintime=explode("^",html_tls_specialchars($oldvalue));
		for($i=0;$i<count($sintime);$i++)
		{
			$elements[$i]=explode("|",$sintime[$i]);
			$totalexp=$totalexp+($elements[$i][3] * $elements[$i][4]);
		}
	}
	elseif($addtype=="preview")
    {
        $addexp=$addexp;
        $sintime=explode("^",html_tls_specialchars($oldvalue));
        for($i=0;$i<count($sintime);$i++)
        {
            $elements[$i]=explode("|",$sintime[$i]);
            $totalexp=$totalexp+($elements[$i][3] * $elements[$i][4]);
        }
        
        if($nexp=="nexp")
        {
            if($addexpense!="")
            {
                $qu="select count(*) from exp_type where title='".$addexpense."'";
                $res=mysql_query($qu,$db);
                $dd=mysql_fetch_row($res);
                if($dd[0]<1)
                {
                    $qu="insert into exp_type (sno, username, title) values('','".$username."','".$addexpense."')";
                    $res=mysql_query($qu,$db);
                }
            }
        }
    }elseif($addtype=="uploadred")//--------
	{
		$addexp=$addexp;
		$totalexp=0;
		$advance=0;
		$sintime=explode("^",html_tls_specialchars($oldvalue));
		for($i=0;$i<count($sintime);$i++)
		{
			$elements[$i]=explode("|",$sintime[$i]);
			$totalexp=$totalexp+($elements[$i][3] * $elements[$i][4]);
			$advance=$advance+$elements[$i][7];
		}
	}
	
	$total=0;
	$menu->showHeader("accounting","Expenses","2");

$divStyle = (MANAGE_CLASSES == "Y") ? '' : 'style="display:none"';

$folder_name = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));

function getStatus($expType)
{
	global $db;
	$sql =  "select expense_value from expense_defaults where expense_name = '".$expType."'";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_assoc($result);
	return $row[expense_value];
}
?>
<td class=tbldata align="center" valign='top'>
<script src="/BSOS/scripts/jquery-1.8.3.js"></script>
<script language=javascript src=scripts/expensereport.js></script>
<script src=/BSOS/scripts/date_format.js language=javascript></script>
<script src="/BSOS/scripts/tabpane.js" language=javascript></script>
<script src=/BSOS/scripts/common_ajax.js></script>
<script src="scripts/manage_expense_rate.js" language="javascript"></script>
<script src="/BSOS/scripts/Expenses/expense_rates.js" language="javascript"></script>
<!-- For New Calendar --> 
<script type="text/javascript" src="/BSOS/scripts/calendar.js"></script>
<link href="/BSOS/css/fontawesome.css" rel="stylesheet" type="text/css">
<link href="/BSOS/Home/style_screen.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/educeit.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="/BSOS/css/calendar.css">
<!-- 
<script src="/BSOS/scripts/select2.js"></script> -->

<link type="text/css" rel="stylesheet" href="/BSOS/css/uploadfileEmail.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/manage_credentials.css">

<script type="text/javascript" src="/BSOS/scripts/jquery.form.min.js"></script>
<script type="text/javascript" src="/BSOS/scripts/jquery.uploadfileEmail.js"></script>
<script type="text/javascript" src="/BSOS/scripts/attachfilesExpense.js"></script>
<script type="text/javascript" src="/BSOS/scripts/attach_uploadEmail.js"></script>
<script type="text/javascript" language="javascript" src="/BSOS/scripts/email/iframeLoader.js"></script>

<!-- This JS file used to resolve the page jerking when sticky the Main Menu bar -->
<script type="text/javascript" src="/BSOS/scripts/stickyActionMenuBar.js"></script>
<!-- END -->

<script type="text/javascript" src="/BSOS/scripts/select2_V4.0.3.js"></script>

<link rel="stylesheet" type="text/css" href="/BSOS/css/expSelect2.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/select2_V_4.0.3.css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/gigboard/gigboardCustom.css">
<style type="text/css">
.ajax-file-upload-progress {width:100px;}
.ajax-file-upload-red{ margin:4px; font-weight:bold; font-size:14px; color:#474c4f !important; text-shadow:none; box-shadow:none !important; background-color:#fff !important}
.ajax-file-upload-progress{ border: 1px solid #c0c0c0;border-radius: 2px; font-size: 11px;}
.ajax-file-upload-bar{  border-radius: 2px;font-size: 10px; height: 12px;}
.ajax-file-upload-filename{ margin:2px 5px; color:#474c4f; font-family:arial; font-size:12px;}
.ajax-file-upload-filename:hover{color:#01b8f2; text-decoration:none }
.uploadMultiM{ padding-top:20px; height:200px; overflow:auto; font-size:14px;}
.uploadAttachmentNew{width:170px; font-weight:bold; float:left; font-weight:bold; font-size:12px; margin-left:20px}
.uploadInfoNew{ color:#e4685d; font-weight:bold; font-size:11px;}
.uploadMultipAttachNew{width:360px; float:left; margin-left:10px; }
.ajax-file-upload-bar{background-color:#000080}
.uploadMultiM{padding-top: 50px !important;}
.ajax-file-upload-blue {margin:0px; background-color: #d1d4d9;border: 1px solid #979797; border-radius: 4px;color: #474c4f;    font-family: arial;font-size: 12px;font-weight: bold; height: 18px; padding: 2px 0;text-align: center;text-decoration: none;transition: all 0.5s ease-in-out 0s;vertical-align: middle;width: 150px;}
.ajax-file-upload-blue:hover {background: #fff none repeat scroll 0 0;color: #095ba1;}
.ajax-file-upload-filename a:hover {color:#01b8f2;font-weight: normal;}
.select2-container, .select2-drop, .select2-search, .select2-search input {
	width: 230px !important;
}

.select2-container--open .select2-dropdown--below {	
	width: 230px !important;
}
.locationSelect .cdfJoborderBlk .selCdfCheckVal .select2-container, .select2-drop, .select2-search, .select2-search input {
	width: 221px !important; 
}
</style>

<form name="sheet" id="sheet" action="saveexpense.php" method="post" enctype="multipart/form-data">
<input type=hidden id="efrom1" name="efrom1" value="<?php echo $efrom1; ?>">
<input type=hidden name='name' id='name' value="<?php echo $name;?>">
<input type=hidden name='aa' id='aa' value='' />
<input type=hidden name='addr' id='addr' value='' />
<input type=hidden name='sdates' id='sdates' value='' />
<input type=hidden name='expcli1' id='expcli1' value='' />
<input type=hidden name='expenset1' id='expenset1' value='' />
<input type=hidden name='expenseclass' id='expenseclass' value='' />
<input type=hidden name='enotes' id='enotes' value='' />
<input type=hidden name='amount' id='amount' value='' />
<input type=hidden name='exppay' id='exppay' value='' />
<input type=hidden name='expbill' id='expbill' value='' />
<input type=hidden name='exp_billrate[]' id='exp_billrate[]' value='' autocomplete="off" />
<input type=hidden name='quantity' id='quantity' value='' />
<input type=hidden name='oldvalue' id='oldvalue' value='' />
<input type=hidden name='t1' id='t1' value='' />
<input type=hidden name='t2' id='t2' value='' />
<input type=hidden name='val' id='val' value='' />
<input type=hidden name='referer' id='referer' value='' />
<input type=hidden name='amount1' id='amount1' value='' />
<input type=hidden name='adv1' id='adv1' value='' />
<input type=hidden name=addtype value="<?php echo $addtype;?>">
<input type=hidden name=addexp value=<?php echo $addexp; ?>>
<input type="hidden" name="hdnDate" id="hdnDate" />
<input type=hidden name='exp_billrate_val[]' id='exp_billrate_val[]' value='' />

<input type="hidden" name="decimalPref" id="decimalPref" value="<?php echo getDecimalPreference(); ?>"/>
<input type="hidden" name="PAYROLL_PROCESS_BY" value="<?php echo PAYROLL_PROCESS_BY;?>"/>

<input type="hidden" name="updfiles" id="updfiles" value="sdf">
<input type="hidden" name="attachment" id="attachment1" value="">
<input type="hidden" name="asize" id="asize" value="">
<input type="hidden" name="sesstr" id="sesstr" value="">
<input type="hidden" name="attach_folder" id="attach_folder" value="<?php echo $folder_name; ?>">
<input type="hidden" name="download_path" id="download_path" value="">
<input type="hidden" name="ectype" id="ectype" value="">
<input type="hidden" name="filename" id="filename" value="">
<input type="hidden" name="sizefilename" id="sizefilename" value="">
<input type="hidden" name="filetype" id="filetype" value="">
<input type="hidden" name="chk_addval" id="chk_addval" value="">
<input type="hidden" name="add_latest" id="add_latest" value="">
<input type="hidden" name="sesstr_dup" id="sesstr_dup" value="">
<input type="hidden" name="chkfield" id="chkfield" value="">
<input type="hidden" name="con_id" id="con_id" value="">
<input type="hidden" name="expfile" id="expfile" value=""> 


<div id="main">

<table width=100% cellpadding=0 cellspacing=0 border=0 class="ProfileNewUI" align="center">
    <tr>
    <td class="titleNewPad">
        <table width=99% cellpadding=0 cellspacing=0 border=0 class="ProfileNewUI defaultTopRange">
        <tr>
            <td colspan=2><font class=bstrip>&nbsp;</font></td>
        </tr>
        <tr>
            <td align=left><font class=modcaption>&nbsp;Create&nbsp;Expense</font></td>	    
            <td align=right>
		<font class=afontstyle>Use the following form to record expenses
		From&nbsp;<input type=text size=10 name=servicedate id=servicedate maxlength="10" value="<?php echo $servicedate;?>" tabindex="1">	
		<script language='JavaScript'>new tcal ({'formname':'sheet','controlname':'servicedate'});</script>
		</font>
		&nbsp;
		<font class=afontstyle>To&nbsp;<input type=text size=10 name=servicedateto id=servicedateto maxlength="10" value="<?php echo $servicedateto;?>" tabindex="1">
		<script language='JavaScript'>new tcal ({'formname':'sheet','controlname':'servicedateto'});</script>
		&nbsp;		
		<a href=javascript:DateCheck('servicedate','servicedateto')>view</a>
		</font>
	    </td>
        </tr>
		<tr>	
			<td colspan=2><font class=bstrip>&nbsp;</font></td>
			<td ><font class=bstrip>&nbsp;</font></td>
		</tr>        
        <tr>
            <td "align=left" colspan="2">&nbsp;
			
            <font class=afontstyle>Select an Employee to fill the Expenses  :&nbsp;</font>

            	<select class="drpdwnacc" id="expemp_list" name="empnames" onChange="javascript:getEmp()"><option value='<?php echo $emp_uname?>'selected><?php echo $ename; ?></option></select>


			</td>
          
        </tr>
        <tr>
            <td><font class=bstrip>&nbsp;</font></td>
        </tr>
        </table>
    </td>
    </tr>
    <tr class="NewGridTopBg">
	<?php
    if($addexp>1)
    {
        $name=explode("|","fa fa-plus-square~Add&nbsp;Row|fa-trash~Delete Row|fa fa-floppy-o~Submit|fa-ban~Cancel");
        $link=explode("|","javascript:doAdd('". $addexp."')|javascript:doDelete('". $addexp."')|javascript:doSubmit('". $addexp."')|javascript:doCancel()");
    }
    else
    {
        $name=explode("|","fa fa-plus-square~Add&nbsp;Row|fa fa-floppy-o~Submit|fa-ban~Cancel");
        $link=explode("|","javascript:doAdd('". $addexp."')|javascript:doSubmit('". $addexp."')|javascript:doCancel()");

    }
		$heading="";
		$menu->showHeadingStrip1($name,$link,$heading,"left");
    ?>
	</tr>
   	<tr>
	<td>
		<table width=100% border=0 cellpadding=3 cellspacing=0 class="CustomExpTh defaultTopRange CustomTimesheetInput">
            <?php
            if($addexp>=1)
            {
				$display_style="display:none";
				for($a=0;$a<=$addexp;$a++)
				{
					if($elements[$a][5] == 'bil' || $elements[$a][12] == 'bil')
					{
						$display_style="display:block";
						break;
					}	
				}
            ?>
			<tr class="hthbgcolor acc-expenses-bgdark">
				<th width="5%">&nbsp;</th>
				<th align=left width="8%" class=afontstyle>Date</th>
				<th align=left width="20%" class=afontstyle>&nbsp;&nbsp;Assignments</th>
				<th align=left width="12%" class=afontstyle>Expense&nbsp;Type<span class=sfontstyle>*</span></th>
				<th align=left width="10%"><div <?php echo $divStyle;?>><span class=afontstyle>Class</span></div></th>
				<th align=left width="6%" class=afontstyle>Quantity<span class=sfontstyle>*</span></th>
				<th align=left width="7%" class=afontstyle>($)&nbsp;Unit&nbsp;Cost<span class=sfontstyle>*</span></th>
				<th align=left width="8%" class=afontstyle>($)&nbsp;Amount</th>
				<th align=left width="8%" class=afontstyle>($)&nbsp;Advance</th>
				<th align=left width="5%" class=afontstyle>Payable</th>
				<th align=left width="4%" class=afontstyle>Billable</th>
				<th align=left id='disp_bill_rate' style=" <?php echo $display_style;?>;font-size: 13px;" width="8%" class=afontstyle>Bill Amount</th>
			</tr>
            <?php
            $pol=0;
			$tab_index=3;	
			for($r=1;$r<=$addexp;$r++)
            {
				$tab_notes_index=$tab_index+8;
				if($r!=0)
					$tab_index=$tab_index++;
                if($r%2==0)
            	   $class="";
                else
            	   $class="";
  
            ?>
	<tr class="<?php echo $class;?> expense_rate_<?php echo $r; ?> expense_rate_qty<?php echo $r; ?> expense_rate_date<?php echo $r;?>  expense_rate_billable_<?php echo $r; ?> expense_row">
            <td valign="top">
				<label class="container-chk">				
				<input type=checkbox name=eauidis[]  value=<?php echo $r;?> tabindex='<?php echo $tab_index++;?>'>
				<span class="checkmark"></span>
				</label>
			</td>
            <td valign=top align="left">
            <font face=arial size=1>
            <select id="expense_rate_date<?php echo $r;?>" name=sdates class="drpdwnacc rate_date akkenDateSelect" onFocus="javascript:setHiddenDateDropList(this);" onchange="javascript:chkAsgn('<?php echo $condCk_comp."|".$showEmplyoees; ?>','<?php echo $r;?>','<?php echo $new_user; ?>',this);checkRates(this,<?php echo $r;?>,'accounting');" selindex="<?php echo $elements[$pol][0] ?>" tabindex='<?php echo $tab_index++;?>'>
            <?php
            //$thisday2=$thisday2+86440;	Commented to overcome DST problem.
			$thisday2 = getTimeStampByNDays("+1",$thisday2);
            if($elements[$pol][0]=='')
			{
				$elements[$pol][0]=date("Y-m-d",$thisday1);
			}
			while($thisday1<$thisday2)
            {
                print "<option ".sel(date("Y-m-d",$thisday1),$elements[$pol][0])." value=".date("Y-m-d",$thisday1).">".date("m/d/Y l",$thisday1)."</option>";
				//$thisday1=$thisday1+86440;	Commented to overcome DST problem.
				$thisday1 = getTimeStampByNDays("+1",$thisday1);
            }
            $thisday1=$today1;
            $thisday2=$today2;
            ?>
            </select>
            </font>
           </td>

            <td valign=top align="left">
            <font face=arial size=1>
            <?php
				$clidet="0";
				$zque = "SELECT manager,project,client,tsapp,'','',s_date,e_date,jtype,sno,pusername,classid FROM hrcon_jobs WHERE username = '".$new_user."' AND pusername!='' AND ((hrcon_jobs.ustatus IN ('active','closed','cancel') AND (hrcon_jobs.s_date IS NULL OR hrcon_jobs.s_date='' OR hrcon_jobs.s_date='0-0-0' OR (DATE(STR_TO_DATE(s_date,'%m-%d-%Y'))<='".$end_date."'))) AND (IF(hrcon_jobs.ustatus='closed',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$start_date."'),1)) AND (IF(hrcon_jobs.ustatus='cancel',(hrcon_jobs.e_date IS NOT NULL AND hrcon_jobs.e_date<>'' AND hrcon_jobs.e_date<>'0-0-0' AND DATE(STR_TO_DATE(e_date,'%m-%d-%Y'))>='".$start_date."'),1))) AND hrcon_jobs.jtype!='' ORDER BY ustatus,udate desc";

			?>
            <select name=expcli1 id="clientId<?php echo $r;?>" class="drpdwnacc clientId akkenAssgnSelect" onChange=javascript:stautsChanged('<?php echo $pol;?>') tabindex='<?php echo $tab_index++;?>'>
            <?php
            
            $zres=mysql_query($zque,$db);
			$selValue = '';
			$flg = '';
			$exp_companies = array();
			if(mysql_num_rows($zres) > 0)
			{
				while($zrow=mysql_fetch_row($zres))
				{
					if($elements[$pol][1] == "AS" || $elements[$pol][1] == "-1")
					{
						$assignmentIndex = "-1";
					}
					elseif($elements[$pol][1] == "OB" || $elements[$pol][1] == "-3")
					{
						$assignmentIndex = "-3";
					}
					if($zrow[2] != 0)
					{
						$lque="select cname, ".getEntityDispName('sno', 'cname', 1)." from staffacc_cinfo where sno=".$zrow[2];
						$lres=mysql_query($lque,$db);
						$lrow=mysql_fetch_row($lres);
						$companyname1 = stripslashes($lrow[1]);					
						$exp_companies[] = $zrow[2];
					}
					else
						$companyname1=stripslashes($companyname);
					$flg = sel($zrow[10],$elements[$pol][1]);						
					if(strcmp($zrow[8],"AS")==0)
					{
						if($elements[$pol][9] == '' || is_null($elements[$pol][9]) || !isset($elements[$pol][9]))
							$elements[$pol][9] = (sel("-1",$assignmentIndex) == 'selected') ? $zrow[11] : $elements[$pol][9];
						
						print "<option ".sel("-1",$assignmentIndex)." value='AS' id='".$zrow[11]."'>".$companyname1." ( Administrative Staff )</option>";
					  
					  }
					elseif(strcmp($zrow[8],"OB")==0)
					{
					  if($elements[$pol][9] == '' || is_null($elements[$pol][9]) || !isset($elements[$pol][9]))
							$elements[$pol][9] = (sel("-3",$assignmentIndex) == 'selected') ? $zrow[11] : $elements[$pol][9];
							
						print "<option ".sel("-3",$assignmentIndex)." value='OB' id='".$zrow[11]."'>".$companyname1."( On Bench )</option>";
					}
					else if(strcmp($zrow[8],"OV")==0)
					{
						 if($elements[$pol][9] == '' || is_null($elements[$pol][9]) || !isset($elements[$pol][9]))
							$elements[$pol][9] = (sel("OV",$elements[$pol][1]) == 'selected') ? $zrow[11] : $elements[$pol][9];
							
						print "<option ".sel("OV",$elements[$pol][1])." value='OV' id='".$zrow[11]."'>".$companyname1." ( On Vacation )</option>";
					}
					else
					{
						if($elements[$pol][9] == '' || is_null($elements[$pol][9]) || !isset($elements[$pol][9]))
							$elements[$pol][9] = (sel($zrow[10],$elements[$pol][1]) == 'selected') ? $zrow[11] : $elements[$pol][9];
						
						$asgnProjectDissply  = '';
						
						if($lrow[0] != '' && $zrow[1] != '')
							$asgnProjectDissply  = $lrow[1]." - ".$zrow[1];
						elseif($lrow[0] != '')
							$asgnProjectDissply  = $lrow[1];
						elseif($zrow[1] != '')
							$asgnProjectDissply  = $zrow[1];	
							
						echo "<option ".sel($zrow[10],$elements[$pol][1])." value='".$zrow[10]."' id='".$zrow[11]."'> (".$zrow[10].")&nbsp;&nbsp;".stripslashes($asgnProjectDissply)."</option>";
					}
					if($flg=='selected')
					{
						$selValue = 'selected';
						$clidet=$zrow[2];
					}
				}
			}
			else
			{
				 echo "<option value='' selected> No Assignments </option>";
			}
			
			   
		    ?>
            </select>
            </font>
        	</td>
          <td align=left><font face=arial size=1>
            <?php  
			    $defaultRatesClass = new defaultRatesDetails();
			    $empSno = $defaultRatesClass->getEmployeeId($new_user);
			    $employees_condition = '';$snoids=array();$expenseTypes=array();
			    $employees_condition .= ' FIND_IN_SET(\''.$empSno.'\',emp_list)> 0';
			    $empqry = 'SELECT sno,title,code FROM exp_type WHERE '.$employees_condition.' OR emp_list IS NULL  ORDER BY title';
				//echo $empqry;
                            $empRes=mysql_query($empqry,$db);
			    while($obj = mysql_fetch_object($empRes))
                            { 
			                 $result =array();
		                         $result['sno']= $obj->sno;
		                         $result['title']= $obj->title;
		                         $result['code']= $obj->code; 
					 // $expenseTypes[] = array('sno' => $obj->sno, 'title' => $obj->title,'code'=>$obj->code);
					 array_push($snoids,$obj->sno);
					 array_push($expenseTypes,$result);
                           
                            }
			   //  echo "<pre>";print_r($expenseTypes);echo "</pre>";
			    $companies_condition = '';
			    $cnt_comp_exp = count($exp_companies);
				
			    for($e=0; $e < $cnt_comp_exp; ++$e)
			    {
					$companies_condition .= ' OR FIND_IN_SET(\''.$exp_companies[$e].'\',users)> 0';
			    }
			    $qu = 'SELECT sno,title,code FROM exp_type WHERE (users IS NULL )'.$companies_condition.'  ORDER BY title';
                            $res=mysql_query($qu,$db);
			    while($obj = mysql_fetch_object($res))
                            {        
			    	$result =array();
                         	$result['sno']= $obj->sno;
                         	$result['title']= $obj->title;
                         	$result['code']= $obj->code; 
				if(!in_array($obj->sno,$snoids)){
					array_push($expenseTypes,$result);
				} 
                             }
			     /** Sort the expense type array**/
			     $titles = array();
				 
			     foreach ($expenseTypes as $e) {
					$titles[] = $e['title'];
			     }
 
                             array_multisort($titles, SORT_ASC, $expenseTypes);
                
                             //echo "<pre>";print_r($expenseTypes);echo "</pre>";
				
            ?>
            <select id="expense_rate_<?php echo $r; ?>" class="drpdwnacc rate_type akkenExpTypeSelect" name=expenset1 tabindex='<?php echo $tab_index++;?>' onChange='javascript:checkRates(this,<?php echo $r; ?>,"accounting")'>
            <option value=''>--Select--</option>
            <?php
			
            foreach($expenseTypes as $dres)
            {
                print "<option value='".$dres['sno']."' ".sel($dres['sno'],$elements[$pol][2]).">".$dres['title']." - ".$dres['code']." </option>";
            }
            ?>
            </select>

					</font>
            </td>
			<td><div <?php echo $divStyle;?>>
				<font face="arial" size="1">
				<?php classSelBox($aryClasses, 'expenseclass', $elements[$pol][9]);?> 
				</font>
				</div>
			</td>
			<td>
            <font face=arial size=1><input id="expense_rate_qty_<?php echo $r; ?>" type=text class="exp_qty" name=quantity size=4  value="<?php echo number_format($elements[$pol][3],2,'.','');?>" onBlur="javascript:doCalc1(<?php echo $pol;?>,<?php echo $r;?>);" tabindex='<?php echo $tab_index++;?>'></font>
            </td>
            <td>
            <font face=arial size=1><input type=text class="exp_amount" name=amount size=10  maxlength=10 value="<?php echo number_format($elements[$pol][4],$decimalPref,'.','');?>" onBlur="javascript:doCalc(<?php echo $pol;?>,<?php echo $r;?>)" tabindex='<?php echo $tab_index++;?>' ></font>
            </td>
            <td>
            <font face=arial size=1><input type=text class="exp_amount1" name=amount1 size=10 value="<?php echo number_format(($elements[$pol][3]* $elements[$pol][4]),$decimalPref,'.','');?>" disabled></font>
            </td>
			<td>
            <font face=arial size=1><input type=text class="adv1" name=adv1 size=10 value="<?php echo number_format($elements[$pol][7],$decimalPref);?>" onBlur="javascript:doAdvanceCalc(<?php echo $pol;?>,<?php echo $r;?>)" tabindex='<?php echo $tab_index++;?>'></font>
            </td>
            <td>
			<?php
				$checkboxDisabled = sele('pay',$elements[$pol][11]);
				$checkboxAttributes = $checkboxDisabled;
			?>
			<font face=arial size=1>
				<label class="container-chk">
				<input  type="checkbox" name="exppay" value="pay" <?php echo $checkboxAttributes; ?> tabindex="<?php echo $tab_index++;?>" />
				<span class="checkmark"></span>
				</label>
			</font>
            </td>
			<td>
			<?php
				$checkboxDisabled = ((("AS" == $elements[$pol][1] || "OV" == $elements[$pol][1] || "OB" == $elements[$pol][1] || "" == $elements[$pol][1]) && ( "0" == $elements[$pol][10] || trim($elements[$pol][10]) == "") && ($clidet == "0" || $clidet == "")) || ("(earn)" == substr($elements[$pol][1], 0, 6)))?" disabled ":sele('bil',$elements[$pol][5]);
				$checkboxDisabled = sele('bil',$elements[$pol][12]);
				$checkboxAttributes = $checkboxDisabled;
				//$checkboxAttributes = ($checkboxAttributes != "" ) ? 'checked' : '';
				
			?>
              
			<font face=arial size=1>
				<label class="container-chk">
				<input type="checkbox" id="expense_rate_billable_<?php echo $r; ?>" class="exp_billable" name="expbill" value="bil" <?php echo $checkboxAttributes; ?> tabindex='<?php echo $tab_index++;?>' onClick='hideBillRate(this,<?php echo $pol;?>);checkBillRates(this,<?php echo $r; ?>,"accounting");'>
				<span class="checkmark"></span>
				</label>
			</font>
            </td>
			<?php
			$style_billable="display:none";
			if($elements[$pol][5] == "bil")
			{	
				?>
				<td id='bill_rate_text<?php echo $pol;?>' style="display:block" valign="middle" class="exp_billable_cont"><font face=arial size=1>
				<input type=text name="exp_billrate[]" class="exp_billable_amt exp_all_billrate_<?php echo $r; ?>" id="exp_billrate[]" value="<?php echo number_format($elements[$pol][8],$decimalPref,'.','');?>" onchange ="return validateDecimalPref(this,event);" onclick="javascript: getAlertBillAmount(this,<?php echo $r; ?>);"  size=10 maxlength=10 tabindex="<?php echo $tab_index++;?>" autocomplete = 'off'></font>				
				</td>
				<input type=hidden name="exp_billrate_val[]" id="exp_billrate_val_<?php echo $r; ?>" class="exp_billable_val"  value="<?php echo number_format($elements[$pol][13],$decimalPref);?>" onchange ="return validateDecimalPref(this,event);" size=10 maxlength=10 tabindex="<?php echo $tab_index++;?>">				
			   <?php
		   	}
			else if($elements[$pol][12] == "bil")
			{	 
				?>
				<td id='bill_rate_text<?php echo $pol;?>' style="display:block" valign="middle" class="exp_billable_cont"><font face=arial size=1>
				<input type=text name="exp_billrate[]" id="exp_billrate[]" class="exp_billable_amt exp_all_billrate_<?php echo $r; ?>" value="0.00" onchange ="return validateDecimalPref(this,event);" onclick="return getAlertBillAmount(this,<?php echo $r; ?>);" size=10 maxlength=10 tabindex="<?php echo $tab_index++;?>" autocomplete = 'off' ></font>
				</td>
				<input type=hidden name="exp_billrate_val[]" id="exp_billrate_val_<?php echo $r; ?>" class="exp_billable_val" value="<?php echo number_format($elements[$pol][13],$decimalPref);?>" onchange ="return validateDecimalPref(this,event);" size=4 maxlength=10 tabindex="<?php echo $tab_index++;?>">				
			   <?php
		   	}
			else
			{	
				?>
				<td id='bill_rate_text<?php echo $pol;?>' style="display:block" valign="middle" class="exp_billable_cont"><font face=arial size=1>
				<input type=hidden name="exp_billrate[]" id="exp_billrate[]" class="exp_billable_amt exp_all_billrate_<?php echo $r; ?>" value="<?php echo number_format($elements[$pol][8],$decimalPref);?>" onchange ="return validateDecimalPref(this,event);"  onclick="return getAlertBillAmount(this,<?php echo $r; ?>);"  size=10 maxlength=10 tabindex="<?php echo $tab_index++;?>" autocomplete = 'off'></font>
				</td>
				<input type=hidden name="exp_billrate_val[]" id="exp_billrate_val_<?php echo $r; ?>" class="exp_billable_val" value="<?php echo number_format($elements[$pol][13],$decimalPref);?>" onchange ="return validateDecimalPref(this,event);" size=4 maxlength=10 tabindex="<?php echo $tab_index++;?>">				
			   <?php
		   	}
               
			?>
			 </tr>
			<tr>
			<td></td>
           	<td colspan="10">
				<font class=afontstyle>
				<i class="fa fa-tasks fa-lg"></i> <b>Notes</b><input class="ExpCreNotesAdd ExpNotesAddRow_<?php echo $r; ?>"  type=text name=enotes size=66 maxlength=67 value="<?php echo  html_tls_specialchars(stripslashes($elements[$pol][6]));?>" tabindex='<?php echo $tab_notes_index;?>'> </font>
           	 </td>
			</tr>
			<?php
			 $pol++;
			 }
            }
            ?>
           

            <tr>
            <td colspan=12 align=left>
            <table cellpadding=0 cellspacing=0 border=0 width=100%>
            <tr class="hthbgcolor">
                <td align=left width=92% col><font class=hfontstyle>&nbsp;&nbsp;<strong>($)Total Expenses :</strong> </font></td>
                <td width="8%"><font class=hfontstyle>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<SPAN id=totalexp><?php echo number_format($totalexp,$decimalPref,".",""); ?></span></font></td>
            </tr>
            <tr>
                <td align=left><font class=hfontstyle><strong>&nbsp;&nbsp;($)Advance :</strong></font></td>
                <td>&nbsp;&nbsp;&nbsp;<input type=text name=advance size=10  value="<?php echo number_format($advance,$decimalPref,'.',''); ?>" onBlur="javascript:doAdvance();doshow(this)" disabled tabindex='<?php echo $tab_index++;?>'>
				</td>
            </tr>
            <tr class="hthbgcolor">
                <td align=left><font class=hfontstyle>&nbsp;&nbsp;<strong>($)Balance :</strong> </font></td>
                <td><font class=hfontstyle>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<SPAN id=balance><?php echo  number_format(($totalexp-$advance),$decimalPref,".",""); ?></span></font></td>
            </tr>
            </table>
            </td>
            </tr>

    </table>
    </td>
    </tr>

    <tr>
    <td class="expRemarks">
    <table cellpadding=0 cellspacing=0 border=0 width=100% align=center>
    <tr>
        <td align=left><font class=afontstyle>&nbsp;Remarks</font></td>
        <td align=left><textarea name=details rows=3 cols=50 tabindex='<?php echo $tab_index++;?>'><?php echo html_tls_specialchars(stripslashes($details),ENT_QUOTES);?></textarea></td>
    </tr>
    <tr>
    <td><img src=/BSOS/images/white.jpg width=10 heigh=10></td>
    </tr>
    <tr>
        <td align=left><font class=afontstyle>&nbsp;Upload Expenses files</font>
        	<div class="uploadInfoNew" style="margin: 1px 5px;"> Attached files may not exceed a total of 20MB.</div>
        </td>
        <td align=left>
        	<div style="position:relative;">
        	<div class="uploadInfoNew" style="border: 1px solid #ccc;font-size: 12px;font-weight: normal;left: 23%;padding: 2px 3px;position: absolute;    top: -5px;width: 65%;">To upload multiple files, browse to the file folder, hold down the CTRL key and click on each file name to select. You can also use 'Browse Files' multiple times to select one file at a time</div></div>
        	<div  class="uploadMultipAttachNew">
				<div id="multiplefileuploader">Browse Files</div>
			</div>
			<div style="clear:both"></div>
        	
        </td>
    </tr>
    </table>
    </td>
    </tr>
    <tr>
    <td>
    <img src=/BSOS/images/white.jpg width=10 heigh=10></td>
    </tr>
	<tr class="NewGridBotBg">
	<?php
    if($addexp>1)
    {
        $name=explode("|","fa fa-plus-square~Add&nbsp;Row|fa-trash~Delete Row|fa fa-floppy-o~Submit|fa-ban~Cancel");
        $link=explode("|","javascript:doAdd('". $addexp."')|javascript:doDelete('". $addexp."')|javascript:doSubmit('". $addexp."')|javascript:doCancel()");
    }
    else
    {
        $name=explode("|","fa fa-plus-square~Add&nbsp;Row|fa fa-floppy-o~Submit|fa-ban~Cancel");
        $link=explode("|","javascript:doAdd('". $addexp."')|javascript:doSubmit('". $addexp."')|javascript:doCancel()");

    }
		$heading="expenses.gif~Create&nbsp;Expense";
		//$menu->showHeadingStrip1($name,$link,$heading);
    ?>
	</tr>
</table>
</div>
<script>document.sheet.empnames.focus();</script>
<input type=hidden name=amt_count value="<?php echo $pol;?>">
</form>
</td>
<?php
	$menu->showFooter();
?>
<form action="searchresults.php" name=search method="get">
<input type=hidden name='search' id='search' value='' />
<input type=hidden name='type' id='type' value='' />
</form>
<form name=expense method=post action="newexpense.php">
<input type=hidden name='addexp' id='addexp' value=<?php echo $addexp; ?>>
<input type=hidden name='empnames' id='empnames' value=<?php echo $emp_uname?> />
<input type=hidden name='file2' id='file2' value="" />
<input type=hidden name='addtype' id='addtype' value='' />
<input type=hidden name='oldvalue' id='oldvalue' value=<?php echo $oldvalue; ?>>
<input type=hidden name='advance' id='advance' value='' />
<input type=hidden name='val' id='val' value='' />
<input type=hidden name='servicedate' id='servicedate' value='' />
<input type=hidden name='servicedateto' id='servicedateto' value='' />
<input type=hidden name='addexpense' id='addexpense' value='' />
<input type=hidden name='nexp' id='nexp' value='' />
<input type=hidden name='details' id='details' value=<?php echo $details; ?> >
</form>
<?php if($dd[0]>0){ echo "<script>document.sheet.addexpense.focus();</script>";} ?>
</body>
<script>
	$(document).ready(function(){
		$("select[name='expenseclass']").addClass("akkenClassSelect");
		$(".drpdwnacc:not(#expemp_list)").select2({minimumResultsForSearch: -1});
		$("#expemp_list").select2();
	
		$("#expemp_list").select2({
    	
        //placeholder: "Search for Employees",
        minimumInputLength: 0,
        closeOnSelect: true,
        ajax: {
            type: "POST",
            url: "/include/timesheet/getSelectorData.php",
            dataType: 'json',
            quietMillis: 500,
            delay: 500,
            data: function (params) {
            	var empids = $('#expemp_list').val();
				var queryParameters = {
				  q: params.term,
				  page: params.page,
				  getModule : 'Accounting',
				  getServicedate :'<?php echo $servicedate;?>',  
				  getServicedateto :'<?php echo $servicedateto;?>',
				  getEmployeeSearchVal: params
				}
				return queryParameters;
			},
			processResults: function (data, params) {
			    params.page = params.page || 1;
			    return {
			        results: data.results,
			        pagination: {
			            more: (params.page * 10) < data.count_filtered
			        }
			    };
			},
			cache: true
		},
            
        language: {
	       	noResults: function(){
	           return "No Employees Found";
	       	},
	       	
	   	},
        escapeMarkup: function (m) {
        	return m; 
        }
    });

});
	
</script>
</html>