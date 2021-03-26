<?php
	require_once('global.inc');
	require_once('timesheet/class.timeintimeout.php');
	
	$XAJAX_ON	= "YES";
	$XAJAX_MOD	= "NewTimeSheets";

	global $db;
	$GridHS	= true;

	require_once('Menu.inc');

	$titleTxt = "Timesheets";
	$menu=new EmpMenu();
	$menu->showHeader("accounting",$titleTxt,"1|");

	/* Therapy Source Custom TimeSheet SESSION */
		$_SESSION['AddCustomTimeSheetNotes']	= array();
		$_SESSION['AddPersonTimeSheetNotes']	= array();
		$_SESSION['TimeSheetNotesTotalSize']	= 0;
	/* END */
	 
	$objTimeInTimeOut	= new TimeInTimeOut($db);
	$layout_preference	= $objTimeInTimeOut->getTSLayoutPreference();

	if(!isset($val) || $val == "")
	{
		$thisday2=mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y"));
		$servicedateto=date("m/d/Y",$thisday2);
                //Time Sheet Load Optimization-Query changes : Modified the query for default 3 months date range and also matched the query conditions with grid query
		$zque="select ".tzRetQueryStringDate('DATE_SUB(MAX(par_timesheet.edate),INTERVAL 3 MONTH)','Date','/')." AS SubtractDate,".tzRetQueryStringDate('MAX(par_timesheet.edate)','Date','/').",".tzRetQueryStringDate('MIN(par_timesheet.sdate)','Date','/')." from par_timesheet LEFT JOIN timesheet_hours AS timesheet ON par_timesheet.sno=timesheet.parid where par_timesheet.rstatus='' and timesheet.status = 'ER'";
		$zres=mysql_query($zque,$db);
		if(mysql_num_rows($zres)>0)
		{
			$zrow=mysql_fetch_row($zres);
			mysql_free_result($zres);
			if(!is_null($zrow[0]))
			{
				$servicedate=$zrow[0];
				$servicedateto=$zrow[1];
                                $servicedatedefault = $zrow[2];//Time Sheet Load Optimization
			}
			else
			{
				$thisday1=mktime(date("H"),date("i"),date("s"),date("m"),date("d")-6,date("Y"));
				$servicedate=date("m/d/Y",$thisday1);
				$servicedateto=date("m/d/Y");
                                $servicedatedefault= $servicedate;//Time Sheet Load Optimization
			}
		}
		else
		{
			$thisday1=mktime(date("H"),date("i"),date("s"),date("m"),date("d")-6,date("Y"));
			$servicedate=date("m/d/Y",$thisday1);
			$servicedateto=date("m/d/Y");
                        $servicedatedefault= $servicedate;//Time Sheet Load Optimization
		}
	}
	else
	{
		if($val=="serv")
		{
			$thisday1=$t1;
			$servicedate=date("m/d/Y",$t1);                 
			$servicedateto=$t2;
			$t21=explode("/",$t2);
			$thisday2= mktime (0,0,0,$t21[0],$t21[1],$t21[2]);
			$todaf=date("Y-m-d",$thisday2);
			$tod=date("Y-m-d",$t1);     
			$sno=$addr1;  
                        $servicedatedefault= $servicedate;//Time Sheet Load Optimization
		}
		else if($val=="servto")
		{
			$servicedate=$t1;                         
			$servicedateto=date("m/d/Y",$t2);  
			$t11=explode("/",$t1);
			$thisday1= mktime (0,0,0,$t11[0],$t11[1],$t11[2]);
			$todaf=date("Y-m-d",$t2);
			$tod=date("Y-m-d",$thisday1);
			$sno=$addr1;
                        $servicedatedefault= $servicedate;//Time Sheet Load Optimization
		}
		else
		{
			$t11=explode("/",$servicedate);
			$thisday1= mktime (0,0,0,$t11[0],$t11[1],$t11[2]);
			$tod=date("Y-m-d",$thisday1);
			$t21=explode("/",$servicedateto);				 
			$thisday2= mktime (0,0,0,$t21[0],$t21[1],$t21[2]);
			$todaf=date("Y-m-d",$thisday2);
                        $servicedatedefault= $servicedate;//Time Sheet Load Optimization
		}
    }

	if(PAYROLL_PROCESS_BY_MADISON=="MADISON")
	{
		$processedEmpMenuItem = "|approve.gif~Processed&nbsp;Time&nbsp;Sheets";
		$processedEmpLinkItem = "|processedpaydatalist.php";
	}

	$txtsdate = date("m/d/Y",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
	$txtedate = date("m/d/Y",mktime(0,0,0,date("m"),date("d"),date("Y")));
?>
<script type="text/javascript" src="/BSOS/scripts/common.js"></script>
<script type="text/javascript" src="/BSOS/scripts/preferences.js"></script>
<script type="text/javascript" src="/BSOS/scripts/common_ajax.js"></script>
<script type="text/javascript" src="/BSOS/scripts/date_format.js"></script>
<script type="text/javascript" src="/BSOS/scripts/ts_menu.js"></script>
<script type="text/javascript" src="scripts/validatetimefax.js"></script>

<script type="text/javascript">

function openNewWindow()
{
	var v_heigth	= 600;
	var v_width	= 1200;

	var form	= document.timesheet;

	var result		= gridActData[gridRowId][13];
	var tito		= gridActData[gridRowId][10];
	var ts_sno		= gridActData[gridRowId][14];
	var layout_arr 		= tito.split("(");
	var layout 		= trim(layout_arr[0]);

// Timesheet grid load optimization -- add param in url to load the grid with updated defualt date ranges after any event occurs on any type of timesheets ex: update status inside edit timesheet screen 
		
	if(layout == 'In & Out')
		var url = "/include/showtimeintimeout.php?sno="+result+"&openerType=Default"; 
	else if(layout == 'UOM')//UOM timesheet page for edit timesheets
		var url = "uom_showfxdet.php?sno="+result+"&openerType=Default";
	else if(layout == 'Custom')//Custom timesheet page for edit Custom timesheets
		var url = "custom_showfxdet.php?sno="+result+"&openerType=Default";
	else if(layout == 'Clock In & Out')//Custom timesheet page for edit Custom timesheets
		var url = "cico_showfxdet.php?sno="+result+"&openerType=Default&ts_sno="+ts_sno;
	else 
		var url = "new_showfxdet.php?sno="+result+"&openerType=Default";

	var name	= "submitedtimesheet";
	var top1	= (window.screen.availHeight-v_heigth)/2;
	var left1	= (window.screen.availWidth-v_width)/2;
	var remoter	= window.open(url, name, "width="+v_width+"px,height="+v_heigth+"px,resizable=yes,scrollbars=yes,left="+left1+"px,top="+top1+"px,status=0");
	remoter.focus();
}

function timeintimeout() 
{
	var win	= window;
	var v_width	= 1280;
	var v_heigth	= 800;
	var top		= (win.screen.availHeight - v_heigth) / 2;
	var left	= (win.screen.availWidth - v_width) / 2;

	var title	= "TimeInTimeOut";
	var url		= "/include/timeintimeout.php?module=Accounting"
	var params	= "width=1280, height=630, resizable=yes, scrollbars=yes, status=0, left=" + left + ", top=" + top;
	var obj_win	= win.open(url, title, params);
	obj_win.focus();
}

window.mm_menu_0515130056_0 = new Menu("root0",0,0,"Verdana, Arial, Helvetica, sans-serif",10,"#000000","#000000","#EFEFEF","#CCCCCC","left","middle",3,0,300,-5,7,true,false,true,1,true,true);
mm_menu_0515130056_0.addMenuItem("Regular", "javascript:doHistory();");
mm_menu_0515130056_0.addMenuItem("Time&nbsp;In&nbsp;&&nbsp;Time&nbsp;Out", "javascript:timeintimeout();");
mm_menu_0515130056_0.fontWeight="bold";
mm_menu_0515130056_0.hideOnMouseOut=true;
mm_menu_0515130056_0.bgColor='#555555';
mm_menu_0515130056_0.menuBorder=1;
mm_menu_0515130056_0.menuLiteBgColor='#FFFFFF';
mm_menu_0515130056_0.menuBorderBgColor='#777777';
mm_menu_0515130056_0.writeMenus();

var pageName	= 'MainGrid';
function doHistory()
{
	var v_width  = 1200;
	var v_heigth = 600;
	var top=(window.screen.availHeight-v_heigth)/2;
	var left=(window.screen.availWidth-v_width)/2;
	var remote = window.open('/include/new_timesheet.php?module=Accounting','Timesheet','width=1200,height=600,resizable=yes,scrollbars=yes,status=0,left='+left+',top='+top);
	remote.focus();
}
function regular_uom()
{//new window for UOM timesheet
	var v_width  = 1200;
	var v_heigth = 600;
	var top=(window.screen.availHeight-v_heigth)/2;
	var left=(window.screen.availWidth-v_width)/2;
	var remote = window.open('/include/uom_timesheet.php?module=Accounting','Timesheet','width=1200,height=750,resizable=yes,scrollbars=yes,status=0,left='+left+',top='+top);
	remote.focus();
}

//new window for Custom timesheet
function custom() 
{
	var v_width	= 1200;
	var v_heigth	= 600;
	
	var top		= (window.screen.availHeight-v_heigth)/2;
	var left	= (window.screen.availWidth-v_width)/2;
	
	var remote	= window.open('/include/custom_timesheet.php?module=Accounting','Timesheet','width=1200,height=600,resizable=yes,scrollbars=yes,status=0,left='+left+',top='+top);
	remote.focus();
}
</script>
<style>
.active-column-6 .active-box-resize {}
.active-column-6 {width: 90px;}
.dynsndiv {	width: 100%;height:100%;top:0px;z-index:9998;position:fixed !important;	filter:alpha(opacity=50);background-color:#000;	opacity:0.55;}
div#tcal{ z-index:9999}
.alert-ync-text-mt{ color:#474c4f}
.fa.fa-calendar{margin-left: 5px;}
.titleNewPad{ padding-top:10px;}
</style>
</script>
<script type="text/javascript" src="/BSOS/popupmessages/scripts/popupMsgArray.js"></script>
<script type="text/javascript" src="/BSOS/popupmessages/scripts/popup-message.js"></script>
<script type="text/javascript" src="/BSOS/scripts/calendar.js"></script>
<link href="/BSOS/css/fontawesome.css" rel="stylesheet" type="text/css">
<link href="/BSOS/Home/style_screen.css" rel="stylesheet" type="text/css">
<link href="/BSOS/css/educeit.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="/BSOS/css/calendar.css">
<link rel="stylesheet" href="/BSOS/popupmessages/css/popup_message.css" media="screen" type="text/css">
<link rel="stylesheet" href="/BSOS/css/popup_styles.css" media="screen" type="text/css">
<link type="text/css" rel="stylesheet" href="/BSOS/css/timeSheetselect2.css">
<style type="text/css">
.checkmark{ top:0px}
.active-column-0{ width:40px !important}
</style>
<form action="empfaxhis.php" name="timesheet" method="post">
<input type=hidden name="aa" id="aa">
<input type=hidden name="addr" id="addr" value=<?php echo $addr;?>>
<input type=hidden name="t1" id="t1" value=<?=$tod?>>
<input type=hidden name="t2" id="t2" value=<?=$todaf?>>
<input type=hidden name="val" id="val">
<input type="hidden" name="getApproveStatus" id="getApproveStatus">
<input type="hidden" name="getempusername" id="getempusername">
<input type="hidden" name="details" id="details">
<input type="hidden" name="rsdate" id="rsdate">
<input type="hidden" name="redate" id="redate">
<input type=hidden name="history" id="history" value="no">
<input type=hidden name="Par_Timesheet_Val" id="Par_Timesheet_Val" value="<?php echo $Par_Timesheet_Val;?>">
<input type=hidden name="Approve_Status" id="Approve_Status">
<div id="tque"></div>
<div id="oque"></div>
	<div id="main">
		<td valign="top" align="center">
			<table width="100%" cellpadding="0" cellspacing="0" border="0" class="ProfileNewUI" align="center">
				<div id="content">
					<tr>
						<td class="titleNewPad">
							<table width="100%" cellpadding="0" cellspacing="0" border="0" class="defaultTopRange">								
								<tr>
									<td><font class="modcaption">&nbsp;&nbsp;Timesheets</font></td>
									<td align="right"><font class="afontstyle">Following are the submitted timesheets that needs to be review for approval.</font></td>
									<td ><font class="bstrip">&nbsp;</font></td>
								</tr>
								<tr>
									<td><font class="bstrip">&nbsp;</font></td>
									<td align="right"><span>From</span>
									<span class="TMEDateBg"><input type="text" size="10"  maxlength="10" name="servicedate" id="servicedate"   value="<?php echo $servicedate;?>"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedate'});</script>
									<span class="FontSize-16 TMEPadLR-6">To</span><input type="text" size="10" name="servicedateto" id="servicedateto"  maxlength="10" value="<?php echo $servicedateto;?>"><script language='JavaScript'>new tcal ({'formname':window.form,'controlname':'servicedateto'});</script></span><span class="TMEDateViewBtn"><a href=javascript:DateCheck('servicedate','servicedateto')><i class="fa fa-eye fa-lg"></i> View</font></a></span></td>
									<td ><font class="bstrip">&nbsp;</font></td>
								</tr>
                                                                <!--Time Sheet Load Optimization -Added table for Grid head timesheet hours display-->
                                                                <table width="99%" id="TimesheetGridLoadOpt" name="TimesheetGridLoadOpt"  cellpadding="0" cellspacing="0" border="0" class="ProfileNewUI" align="center">
								<?php
								$minDateValue = date("Y-m-d",strtotime($servicedate));
								$maxDateValue = date("Y-m-d",strtotime($servicedateto));
								$tque="SELECT ROUND(SUM(t.hours),2) FROM timesheet_hours t LEFT JOIN par_timesheet p ON t.parid=p.sno WHERE p.rstatus='' AND t.status ='ER' AND ".tzRetQueryStringDate('p.sdate','YMDDate','-').">='".$minDateValue."' AND ".tzRetQueryStringDate('p.edate','YMDDate','-')."<='".$maxDateValue."' AND p.template IN ('Regular','TimeInTimeOut') ";
								$tres=mysql_query($tque,$db);
								$trow=mysql_fetch_row($tres);
								if($trow[0]=="")
									$total_hours="0:00";
								else
									$total_hours=$trow[0];
									
								//UOM  timesheet
								$tque_uom="SELECT ROUND(SUM(t.hours),2) FROM timesheet_hours t LEFT JOIN par_timesheet p ON t.parid=p.sno WHERE p.rstatus='' AND t.status ='ER' AND ".tzRetQueryStringDate('p.sdate','YMDDate','-').">='".$minDateValue."' AND ".tzRetQueryStringDate('p.edate','YMDDate','-')."<='".$maxDateValue."' AND p.template IN ('UOM') ";
								$tres_uom=mysql_query($tque_uom,$db);
								$trow_uom=mysql_fetch_row($tres_uom);
								if($trow_uom[0]=="")
									$totaluom_hours="0:00";
								else
									$totaluom_hours=$trow_uom[0];
								
								//Custom  timesheet
								$tque_custom="SELECT ROUND(SUM(t.hours),2) FROM timesheet_hours t LEFT JOIN par_timesheet p ON t.parid=p.sno WHERE p.rstatus='' AND t.status ='ER' AND ".tzRetQueryStringDate('p.sdate','YMDDate','-').">='".$minDateValue."' AND ".tzRetQueryStringDate('p.edate','YMDDate','-')."<='".$maxDateValue."' AND p.template = 'Custom' ";
								
								$tres_custom=mysql_query($tque_custom,$db);
								$trow_custom=mysql_fetch_row($tres_custom);
								if($trow_custom[0]=="")
									$totalcustom_hours="0:00";
								else
									$totalcustom_hours=$trow_custom[0];
								?>
                                <tr>
                                <td>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
									<td align="left"><font class="afontstyle">Total hours submitted from <?php echo $servicedate;?> to <?php echo $servicedateto;?> : <b><?php echo $total_hours;?></b></font></td>
								</tr>
								<tr>
									<td  align="left"><font class="afontstyle">Total <b>Days* </b>/ <b>Miles*</b> / <b>Units*</b> submitted from <?php echo $servicedate;?> to <?php echo $servicedateto;?> : <b><?php echo $totaluom_hours;?></b></font></td>
								</tr>
                                
                                <?php if(THERAPY_SOURCE_ENABLED == 'Y'){?>
									<tr>
										<td  align="left"><font class="afontstyle">Total <b>Hours</b> / <b>Days* </b>/ <b>Miles*</b> / <b>Units*</b>  submitted for Custom Timesheet from <?php echo $servicedate;?> to <?php echo $servicedateto;?> : <b><?php echo $totalcustom_hours;?></b></font></td>
									</tr>
								<?php
								}
								?>
                                
                                </table>
                                
                                
                                </td>
                                <td>
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                	
								<tr>
									<td align="right"><font style="color:red !important" class="afontstyle">NOTE: Process Timesheet Rules will be applied only for "Regular Timesheet Layout".<br>NOTE: Timesheets created using the "Create Multiple Timesheets" link will use only Week Rule.</font></td> </tr>
                                </table>
                                
                                </td>
                                
                                </tr>
                               </table>
                                 <!--Time Sheet Load Optimization-->
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="2"><font class="bstrip">&nbsp;</font></td>
						<td ><font class="bstrip">&nbsp;</font></td>
					</tr>
				</div>
	
				<div id="topheader">
					<tr class="NewGridTopBg">
					<?php
						if(DEFAULT_TSR=="Y")
						{
							$ntime_rules="fa-cog~Setup&nbsp;Rules|";
							$ltime_rules="setuprules.php|";
	
							$trque="SELECT COUNT(1) FROM ts_rules WHERE status='ACTIVE'";
							$trres=mysql_query($trque,$db);
							$trrow=mysql_fetch_row($trres);
							if($trrow[0]>0)
							{
								$ntime_rules.="fa-hourglass-half~Process&nbsp;Rules|";
								$ltime_rules.="javascript:applyRules()|";
							}
						}

						$menuname	= $ntime_rules."fa fa-clone~Update Status|fa-newspaper-o~New&nbsp;Timesheet&nbsp";
						$layoutpreferencearr = explode(',',$layout_preference);						
						$menucount = count($layoutpreferencearr);
						
						if(is_array($layoutpreferencearr) && $menucount == 1){
							if(is_array($layoutpreferencearr) && ( $layoutpreferencearr[0] == 1 || $layoutpreferencearr[0] == 2 || $layoutpreferencearr[0] == 3 || $layoutpreferencearr[0] == 4)){
								$menuname	.= "";
							}
						}elseif(is_array($layoutpreferencearr) && $menucount > 1){
							if(is_array($layoutpreferencearr) && (in_array(1,$layoutpreferencearr) || in_array(2,$layoutpreferencearr) || in_array(3,$layoutpreferencearr) || in_array(4,$layoutpreferencearr))){
								$menuname	.= "|droplist";
							}
						}
						$menuname	.="|fa-clipboard~Create&nbsp;Multiple&nbsp;Timesheets&nbsp;|fa-clock-o~Send&nbsp;Reminders|droplist|fa-trash~Delete";

						$name	= explode("|",$menuname);
							
						$linkdata = "javascript:doApproveTimeGrid();";
						
						if(is_array($layoutpreferencearr) && $menucount == 1){
							if(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 1){
								$linkdata	.= "|javascript:doHistory();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 2){
								$linkdata	.= "|javascript:timeintimeout();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 3){
								$linkdata	.= "|javascript:regular_uom();";
							}
							elseif(is_array($layoutpreferencearr) && ($layoutpreferencearr[0] == 4) && (THERAPY_SOURCE_ENABLED == "Y")){
								$linkdata	.= "|javascript:custom();";
							}
						}elseif(is_array($layoutpreferencearr) && $menucount > 1){	

							if(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 1){
								$linkdata	.= " |javascript:doHistory();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 2){
								$linkdata	.= " |javascript:timeintimeout();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 3){
								$linkdata	.= " |javascript:regular_uom();";
							}
							elseif(is_array($layoutpreferencearr) && ($layoutpreferencearr[0] == 4) && (THERAPY_SOURCE_ENABLED == "Y")){
								$linkdata	.= " |javascript:custom();";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(1,$layoutpreferencearr) || in_array(2,$layoutpreferencearr) || in_array(3,$layoutpreferencearr) || in_array(4,$layoutpreferencearr))){
								$linkdata	.= "|";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(1,$layoutpreferencearr))){
								$linkdata	.= "~<a href='javascript:doHistory();'>Regular</a>";
							}

							if(is_array($layoutpreferencearr) && (in_array(2,$layoutpreferencearr))){
								$linkdata	.= "~<a href='javascript:timeintimeout();'>Time&nbsp;In&nbsp;&&nbsp;Time&nbsp;Out</a>";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(3,$layoutpreferencearr))){
								$linkdata	.= "~<a href='javascript:regular_uom();'>UOM</a>";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(4,$layoutpreferencearr)) && (THERAPY_SOURCE_ENABLED == "Y")){
								$linkdata	.= "~<a href='javascript:custom();'>Custom</a>";
							}
						}
						
						$linkdata	.= "|javascript:displayTimeGridAlert();|javascript:;|<a href=\"javascript:doSend('css');\">Notify CSS Users</a>~<a href=\"javascript:doSend('ess');\">Notify ESS Users</a>|javascript:doDeleteTimeSheet()";
						
						$link	= explode("|",$ltime_rules.$linkdata);
						$heading="";
						$menu->showMainGridHeadingStrip1($name,$link,$heading);
					?>
					</tr>
				</div>
				<div id="grid_form">
				  <tr>
					<td>
					<script>
							var gridHeadCol = ["<label class='container-chk'><input type=checkbox name=chk id=chk onClick=mainChkBox_ProcessedRecords()><span class='checkmark'></span></label>","Emp&nbsp;ID","Employee&nbsp;Name","Assignment&nbsp;ID(s)","Cust&nbsp;ID(s)","Customer&nbsp;Name(s)","Work&nbsp;State","Start&nbsp;Date","End&nbsp;Date","Total","Timesheet&nbsp;Layout","Submitted&nbsp;Time"];
							var gridHeadData = ["","<input class=gridserbox type=text name=aw-column1 id=aw-column1 size=15>","<input class=gridserbox type=text name=aw-column2 id=aw-column2 size=5>","<input class=gridserbox type=text name=aw-column3 id=aw-column3 size=15>","<input class=gridserbox type=text name=aw-column4 id=aw-column4 size=15>","<input class=gridserbox type=text name=aw-column5 id=aw-column5 size=15>","<input class=gridserbox type=text name=aw-column6 id=aw-column6 size=15>","<input class=gridserbox type=text name=aw-column7 id=aw-column7 size=15>","<input class=gridserbox type=text name=aw-column8 id=aw-column8 size=15>","<input class=gridserbox type=text name=aw-column9 id=aw-column9 size=15>","<select class=gridserbox name=aw-column10 id=aw-column10 onChange=doGridSearch('search')><option value=''>ALL</option><option value='Regular'>Regular</option><option value='TimeInTimeOut'>In & Out</option><option value='UOM'>UOM</option><option value='Custom'>Custom</option><option value='Clockinout'>Clock In & Out</option></select>","<input class=gridserbox type=text name=aw-column11 id=aw-column11 size=15>"];
							var gridActCol = ["","","","","","","","",""];
							var gridActData = [];
							var gridValue = "Accounting_NewTimeSheets";
							gridSortCol=11;
							gridSort="DESC";
							gridForm=document.forms[0];
							gridSearchResetColumn="";
							initGrids(12);
							gridExtraFields = new Array();
							gridExtraFields['servicedate']='<?php echo $servicedate;?>';
							gridExtraFields['servicedateto']='<?php echo $servicedateto;?>';
                                                        gridExtraFields['servicedatesearched']='<?php echo $servicedatedefault;?>';//Time Sheet Load Optimization
                                                        gridExtraFields['gridTypeValue'] = gridValue;//Time Sheet Load Optimization
							xajax_gridData(gridSortCol,gridSort,gridPage,gridRecords,gridSearchType,gridSearchFields,gridExtraFields);
					</script>
					</td>
				  </tr>
       			 </div>
				<div id="botheader">
					<tr class="NewGridBotBg">
					<?php
						$menuname	= $ntime_rules."fa fa-clone~Update Status|fa-newspaper-o~New&nbsp;Timesheet&nbsp";
						$layoutpreferencearr = explode(',',$layout_preference);						
						$menucount = count($layoutpreferencearr);
						
						if(is_array($layoutpreferencearr) && $menucount == 1){
							if(is_array($layoutpreferencearr) && ( $layoutpreferencearr[0] == 1 || $layoutpreferencearr[0] == 2 || $layoutpreferencearr[0] == 3 || $layoutpreferencearr[0] == 4)){
								$menuname	.= "";
							}
						}elseif(is_array($layoutpreferencearr) && $menucount > 1){
							if(is_array($layoutpreferencearr) && (in_array(1,$layoutpreferencearr) || in_array(2,$layoutpreferencearr) || in_array(3,$layoutpreferencearr) || in_array(4,$layoutpreferencearr))){
								$menuname	.= "|droplist";
							}
						}
						$menuname	.="|fa-clipboard~Create&nbsp;Multiple&nbsp;Timesheets&nbsp;|fa-clock-o~Send&nbsp;Reminders|droplist|fa-trash~Delete";

						$name	= explode("|",$menuname);
							
						$linkdata = "javascript:doApproveTimeGrid();";
						
						if(is_array($layoutpreferencearr) && $menucount == 1){
							if(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 1){
								$linkdata	.= "|javascript:doHistory();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 2){
								$linkdata	.= "|javascript:timeintimeout();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 3){
								$linkdata	.= "|javascript:regular_uom();";
							}
							elseif(is_array($layoutpreferencearr) && ($layoutpreferencearr[0] == 4) && (THERAPY_SOURCE_ENABLED == "Y")){
								$linkdata	.= "|javascript:custom();";
							}
						}elseif(is_array($layoutpreferencearr) && $menucount > 1){	

							if(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 1){
								$linkdata	.= " |javascript:doHistory();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 2){
								$linkdata	.= " |javascript:timeintimeout();";
							}
							elseif(is_array($layoutpreferencearr) && $layoutpreferencearr[0] == 3){
								$linkdata	.= " |javascript:regular_uom();";
							}
							elseif(is_array($layoutpreferencearr) && ($layoutpreferencearr[0] == 4) && (THERAPY_SOURCE_ENABLED == "Y")){
								$linkdata	.= " |javascript:custom();";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(1,$layoutpreferencearr) || in_array(2,$layoutpreferencearr) || in_array(3,$layoutpreferencearr) || in_array(4,$layoutpreferencearr))){
								$linkdata	.= "|";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(1,$layoutpreferencearr))){
								$linkdata	.= "~<a href='javascript:doHistory();'>Regular</a>";
							}

							if(is_array($layoutpreferencearr) && (in_array(2,$layoutpreferencearr))){
								$linkdata	.= "~<a href='javascript:timeintimeout();'>Time&nbsp;In&nbsp;&&nbsp;Time&nbsp;Out</a>";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(3,$layoutpreferencearr))){
								$linkdata	.= "~<a href='javascript:regular_uom();'>UOM</a>";
							}
							
							if(is_array($layoutpreferencearr) && (in_array(4,$layoutpreferencearr)) && (THERAPY_SOURCE_ENABLED == "Y")){
								$linkdata	.= "~<a href='javascript:custom();'>Custom</a>";
							}
						}
						
						$linkdata	.= "|javascript:displayTimeGridAlert();|javascript:;|<a href=\"javascript:doSend('css');\">Notify CSS Users</a>~<a href=\"javascript:doSend('ess');\">Notify ESS Users</a>|javascript:doDeleteTimeSheet()";
						
						$link	= explode("|",$ltime_rules.$linkdata);
						$heading="";
						//$menu->showMainGridHeadingStrip1($name,$link,$heading);
					?>
					</tr>
				</div>
			</table>
		</td>
	</div>
<tr>
<?php
	$menu->showFooter();
?>
</tr>
</form>
<div id="dynsndiv" class="dynsndiv" style="display:none;"></div>
<script type="text/javascript">
$('a').each(function()
{
	if($(this).text() == 'Update Status')
		$(this).attr('class', 'link6 timeupdatestatus');
});
</script>
</body>
</html>