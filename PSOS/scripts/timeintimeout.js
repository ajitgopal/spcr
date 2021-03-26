var elehref;

// SEVENTH DAY RULE SPECIFIC
var dyna_dates		= new Array();
var dyna_list		= '';
var dyna_rowid		= '';
var days_to_check 	= 7;
var service_list 	= '';
var service_sublist	= '';
var pos_weekstartday 	= '';
var pos_weekendday	= '';
var message_seventh_day_rule = '';
var sixth_day_rowid = -1;
var seventh_day_rowid = -1;

var default_weekday 	= ["Sunday", "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
var seventh_day_rule_states = ["california", "ca"];

var service_weekdates 	= new Array();
var service_weekdays 	= new Array();

/* As per US rules, if an employee works for five consecutive days then 
 * on sixth day, max overtime hours = 12.00, beyond that every hour considered under double time and
 * on seventh day, max overtime hours = 8.00, beyond that every hour considered under double time
 */
var sixth_day_max_overtime = 12.00;
var seventh_day_max_overtime = 8.00;

// Flags
var rule_condition1 		= false; // Consecutive Days
var rule_condition2 		= false; // Unique six/seven days
var rule_condition3 		= false; // California state
var first_row_id 		= '';
//var found_nonconsecutive_days 	= false; //Non-consecutive days and overtime calculated found in a workweek

// WEEK RULE SPECIFIC
var week_dates	= new Array();
var week_days	= new Array();

var week_start_day	= '';
var week_end_day	= '';

$(document).ready(function() {
	
	//bind the select2 for employee list on timesheets.
	if (module != "MyProfile" && mode != "edit") {
		$("#empnames").select2();
	}
	
	//for displaying alert message if any timesheet is submit based on the selected date range. - only for ess user
	var ess_user = document.getElementById("ess_user").value;
	var lockdownflag = document.getElementById("lockdown_flag").value;
	if(module == "MyProfile" && ess_user=="YES" && mode=="" && lockdownflag=="dont_allow_duplicate"){
	    getTitoTimesheetDateRangeBeforeSubmit();
	}

	// START - SEVENTH DAY RULE SPECIFIC
	if (seventhdayrule_flag != 0) {
		// Build dates and days list based on service dates

		$(".rowRegularHours").each(function() {
				first_inputid	= $(this).attr("id");
				first_row_id	= first_inputid.split("_").pop(-1);
				return false;
		});

		$("#daily_dates_"+first_row_id+" option").each(function()
		{
			// Build weekdates array
			service_weekdates.push($(this).val());

			// Build weekdays array
			temp_date = parseDate($(this).val());
			temp_day  = default_weekday[temp_date.getDay()];
			service_weekdays.push(temp_day);
		});

		// find position of weekend day in service_weekdays
		if (service_weekdays.indexOf(seventhdayrule_weekendday) > -1) {
			pos_weekendday = service_weekdays.indexOf(seventhdayrule_weekendday);
			pos_weekstartday = pos_weekendday - 6;		// as index starts from 0

			if (pos_weekstartday < 0) {
				pos_weekendday = service_weekdays.indexOf(seventhdayrule_weekendday, pos_weekendday+1);
				pos_weekstartday = pos_weekendday - 6;
			}
		}

		// build the string for 7 consecutive days
		if (pos_weekstartday >= 0) {
			for (i=0; i<days_to_check; i++) {
				new_index = (pos_weekstartday+i);
				service_list += service_weekdates[new_index]+"#";
				if (i<6) { // List for 5 working days + sixth day
					service_sublist += service_weekdates[new_index]+"#";
				}
			}
			service_list = service_list.slice(0, - 1);
			service_sublist = service_sublist.slice(0, - 1);
		}

		// EDIT TIMESHEET: Fill Regular Hours with 0.00 as in Edit timesheet initially OT and DT will be present for 6th day and 7th day.
		if (mode == 'edit'){
			$(".rowRegularHours").each(function() {

				edit_inputid	= $(this).attr("id");
				edit_row_id	= edit_inputid.split("_").pop(-1);

				regular_hours		= $("#daily_rate_0_" + edit_row_id).val();
				overtime_hours		= $("#daily_rate_1_" + edit_row_id).val();
				doubletime_hours	= $("#daily_rate_2_" + edit_row_id).val();

				// To handle NaN and empty values
				regular_hours		= formatNumber(regular_hours);
				overtime_hours		= formatNumber(overtime_hours);
				doubletime_hours	= formatNumber(doubletime_hours);

				$("#singledaytotalhrs_" + edit_row_id).val(parseFloat(regular_hours)+parseFloat(overtime_hours)+parseFloat(doubletime_hours));

				if ($("#daily_rate_0_" + edit_row_id).val() == '' && $("#daily_rate_1_" + edit_row_id).val() != '' && $("#daily_rate_1_" + edit_row_id).val() != 0) {
					$("#daily_rate_0_" + edit_row_id).val("");
				}

				if (pos_weekstartday >= 0 && pos_weekendday <= 6) {

					apply_rule(edit_row_id);
				}
			});
		}
	}
	// END - SEVENTH DAY RULE SPECIFIC

	if (ruletype_flag == "weekrule") {

		$(".rowRegularHours").each(function() {
			id	= $(this).attr("id");
			row_id	= id.split("_").pop(-1);
			return false;
		});

		$("#daily_dates_"+row_id+" option").each(function() {

			week_dates.push($(this).val());

			// Build weekdays array
			temp_date	= parseDate($(this).val());
			temp_day	= default_weekday[temp_date.getDay()];

			week_days.push(temp_day);
		});

		// find position of weekend day in week_days
		if (week_days.indexOf(wk_payroll_weekendday) > -1) {

			week_end_day	= week_days.indexOf(wk_payroll_weekendday);
			week_start_day	= week_end_day - 6;// as index starts from 0

			if (week_start_day < 0) {

				week_end_day	= week_days.indexOf(wk_payroll_weekendday, week_end_day + 1);
				week_start_day	= week_end_day - 6;
			}
		}
	}

	if (module != 'MyProfile') {

		if (mode == 'edit')
		var empacc	= $("#empnames_myprofile").val();
		else{
			var empacc	= $(".employees option:selected").val();	
			$("#empnames_oldvalue").val(empacc);	
		}		
	} else if (module == 'MyProfile') {

		var empacc = $("#empnames_myprofile").val();
	}

	$('form').attr('autocomplete', 'off');

	chainNavigation();

	var el	= document.getElementById('el');

	$('#ischkSelected').click(function() {
		$("#chk").toggle(this.checked);
	});

	$('#ischkSelected1').click(function() {
		$("#chk1").toggle(this.checked);
	});

	$('#ischkSelected2').click(function() {
		$("#chk2").toggle(this.checked);
	});

	 $("#column_select").change(function() {         
		($(this).val() == "col1") ? $("#layout_select1").addClass("erimShow") : $("#layout_select1").removeClass("erimShow");
		($(this).val() == "col2") ? $("#layout_select2").addClass("erimShow") : $("#layout_select2").removeClass("erimShow");
	});

	$("#empnames").change(function() {
		var form	= document.sheet;
		form.action	= "timeintimeout.php?module="+module;

		form.rowcou.value	= "";
		form.val.value		= "";
		form.valtodate.value	= "";

		form.submit();
	});

	// Arrow UP/Down Main Function
	$("#MainTable tr.tr_clone input[type=text]" ).each( function( i, el ) {
		var iclass = $(el).attr('class');
		$(':input.'+iclass).bind('focus', function() {
			$(this).select();
		}).bind('keydown', function(e) {
			if (e.which === 40) {
				var $next = $(this).data('next');
				if ($next != null) {
					$next.select();
				}
			} else if (e.which === 38) {
				var $prev = $(this).data('prev');
				if ($prev != null) {
					$prev.select();
				}
			}
		});
	});

	$('.addtaskdetails').blur(function() {
		var id = $(this).attr('id');
		id = id.replace("taskTB_", "");

		if ($.trim(this.value) == '') {

			this.value = (this.defaultValue ? this.defaultValue : '');
			$("#textlabel_"+id).html("");
			$("#taskTB_"+id).val("");

		} else {

			$("#textlabel_"+id).html(this.value);
		}

		$(this).hide();
		$("#textlabel_"+id).show();
	});

	$('.addtaskdetails').keypress(function(event) {
		if (event.keyCode == '13') {
			var id = $(this).attr('id');
			id = id.replace("taskTB_", "");

			if ($.trim(this.value) == '') {

				this.value = (this.defaultValue ? this.defaultValue : '');
				$("#textlabel_"+id).html("");
				$("#taskTB_"+id).val("");

			} else {

				$("#textlabel_"+id).html(this.value);
			}

			$(this).hide();
			$("#textlabel_"+id).show();
		}
	});

	if (mode == 'edit') {

		var cindex	= $('#dynrowcount').val();

		// WEEK RULE : FILL REGULAR/OVERTIME HOURS WITH 0.00 IN EDIT TIMESHEET, IF TOTAL REGULAR HOURS > MAX. REGULAR HOURS OR IF TOTAL OVERTIME HOURS > MAX. OVERTIME HOURS DECLARED IN PAYROLL SETUP.
		if (ruletype_flag == "weekrule") {

			format_zerovalue	= formatNumber(0);

			$(".rowDoubleTimeHours").each(function() {

				atb_id	= $(this).attr("id");
				dbt_id	= atb_id.split("_").pop(-1);

				regular_hours	= $("#daily_rate_0_" + dbt_id).val();
				overtime_hours	= $("#daily_rate_1_" + dbt_id).val();
				doubletime_hours= $("#daily_rate_2_" + dbt_id).val();

				if (overtime_hours != "") {

					if (regular_hours == "") {

						$("#daily_rate_0_" + dbt_id).val(format_zerovalue);
					}
				}

				if (doubletime_hours != "") {

					if (regular_hours == "") {

						$("#daily_rate_0_" + dbt_id).val(format_zerovalue);
					}

					if (overtime_hours == "") {

						$("#daily_rate_1_" + dbt_id).val(format_zerovalue);
					}
				}
			});

			if (wk_ruleflag == "Y") {

				calculateweekhours();
			}
		}

	} else {

		var cindex	= $('#MainTable tr.tr_clone').length - 1;
	}

	$("a").on('click', function() {

		var click_text	= $(this).text();
		var file_loc	= $(location).attr('href');
		var file_len	= file_loc.indexOf("/include/timeintimeout.php");
		if (file_len > -1 && click_text == 'Add Row') {
			
			add_row();

		} else if (file_len > -1 && click_text == 'Delete Row') {

			delete_row();
		}
	});

	function add_row() {
		var $trLast    = $('#MainTable tbody>tr.tr_clone:last').closest('tr.tr_clone');
		$trLast.find('.select2-select').select2('destroy'); // Un-instrument original row
		
		var $clone	= $trLast.clone();
		
		var hiddenBillable = $('#hiddenBillable').val();
		var hiddenBillableArr = hiddenBillable.split(',');
		var counter = 0;
		
		var tabindexcount	= parseInt($("#tabindexcount").val());
		cindex++;
		
		$clone.attr("id", "row_" + (cindex));
		$clone.find("input[type=text]").val("");
		$clone.find("input[type=hidden]").val("");
		$clone.find(":checkbox").attr("checked",false);
		//$clone.find(":checkbox").val(cindex);
		$clone.find("select").each(function() { this.selectedIndex = 0; });
		$clone.find("label").empty();
		$clone.find("label.taskLabel").attr("onClick","javascript:AddTaskDetails('"+(cindex)+"')");
		$clone.find("font.taskLabel").attr("onClick","javascript:AddTaskDetails('"+(cindex)+"')");
		
		//update ids of elements in row
		$clone.find("*").each(function() {
		
			var id	= this.id || "";
		
			if (id != "") {
		
				var splitid	= id.split('_');
				var rowid	= splitid.pop();
				var fstr	= splitid.join('_');
		
				if (fstr != "") {
					
					this.id	= fstr+'_'+(cindex);
					
					if (fstr.indexOf('edit_snos') == 0)
					{
						var snoname	= $(this).attr('name');
						var editsnos	= snoname.replace(/(\d+)/g, cindex);
		
						$(this).attr('name', editsnos);
					}
		
					if (fstr.indexOf('check') > -1)
					{
						var checkname	= $(this).attr('name');
						var checkbox	= checkname.replace(/(\d+)/g, cindex);
		
						$(this).attr('name', checkbox);
					}
		
					if (fstr.indexOf('daily_dates') > -1)
					{
						var mydate	= $(this).attr('name');
						var dailydate	= mydate.replace(/(\[\d+\]$)/, "["+cindex+"]");
						$(this).attr('name', dailydate);
		
						var date_rowid	= "javascript:getDataOnDate(this.id);";
						$(this).attr('onchange', date_rowid);
					}
		
					if (fstr.indexOf('daily_assignemnt') > -1)
					{
						var myasgn	= $(this).attr('name');
						var dailyassgn	= myasgn.replace(/(\[\d+\]$)/, "["+cindex+"]");
						$(this).attr('name', dailyassgn);
		
						var asgn_rowid	= "javascript:getDataOnAssignment(this.id);";
						$(this).attr('onchange', asgn_rowid);
					}
		
					if (fstr.indexOf('daily_classes') > -1)
					{
						var myclass	= $(this).attr('name');
						var dailyclass	= myclass.replace(/(\[\d+\]$)/, "["+cindex+"]");
		
						$(this).attr('name', dailyclass);
					}
		
					if ((fstr.indexOf("pre_intime") > -1) || (this.id.indexOf("pre_outtime") > -1) || (this.id.indexOf("post_intime") > -1) || (this.id.indexOf("post_outtime") > -1)) {
		
						var tito	= $(this).attr('name');
						var inouttime	= tito.replace(/(\[\d+\]$)/, "["+cindex+"]");
						$(this).attr('name', inouttime);
		
						var preintimeblur	= "javascript:calculateTime(this.id);";
						$(this).attr('onchange', preintimeblur);
					}
		
					if (fstr.indexOf('break_hours') > -1)
					{
						var myhours	= $(this).attr('name');
						var brkhours	= myhours.replace(/(\[\d+\]$)/, "["+cindex+"]");
		
						$(this).attr('name', brkhours);
					}
		
					if (fstr.indexOf('daily_rate_') > -1)
					{
						if (fstr.indexOf('daily_rate_pay_') == -1 && fstr.indexOf('daily_rate_bill_') == -1)
						{
							var myname = $(this).attr('name');
							var cindexpre = cindex-1;
							var flag = false;
		
							myname1 = myname.replace(/(\d+)/, cindex);
							myname2 = myname1.replace(/(\[\d+\])/, "["+cindex+"]");
							$(this).attr('name', myname2);
							if(myname2.indexOf('daily_rate_billable_') > -1)
							{
								if(hiddenBillableArr[counter] == 'Y')
								{
									flag = true;
								}
								else
								{
									flag = false;
								}
								$(this).attr('checked', flag);
								counter++;
							}
		
							var mykeyup = $(this).attr('onkeyup');
							if(typeof(mykeyup) != "undefined")
							{
								var mykeyupsplit = mykeyup.split(',');
								var mykeyupfinal = mykeyupsplit[0]+','+cindex+','+mykeyupsplit[2];
								var mykeyupsplit = mykeyupsplit[3].split('_');
								var mykeyupsplitpop =  mykeyupsplit.pop();
								var mykeyupsplitpopjoin = mykeyupsplit.join('_');
								var mykeyupsplitpopjoinval = mykeyupsplitpopjoin+'_'+cindex+'\')';
								var mykeyupfinal1 = mykeyupfinal+','+mykeyupsplitpopjoinval;
								$(this).attr('onkeyup', mykeyupfinal1);
							}
						}
						else
						{
							var myname0 = $(this).attr('name');
							var splitname = myname0.split('_');
							var rowname = splitname.pop();
							var fstrname = splitname.join('_');
							var myname3 = fstrname+'_'+(cindex);
							$(this).attr('name', myname3);
						}
					}
		
					if (fstr.indexOf('textlabel_') > -1)
					{
						var textlabel	= $(this).attr('id');
						var mylabel	= textlabel.replace(/(\d+)/g, cindex);
						$(this).attr('id', mylabel);
					}
		
					if (fstr.indexOf('addtaskdetails') > -1 || fstr.indexOf('textlabel') > -1)
					{
						var addtask	= $(this).attr('onclick');
						var taskdetails	= addtask.replace(/(\(\d+\)$)/, "("+cindex+")");
		
						$(this).attr('onclick', taskdetails);
					}
		
					if (fstr.indexOf("taskTB") > -1)
					{
						var mytasktb	= $(this).attr("name");
						var newtask	= mytasktb.replace(/(\[\d+\]$)/, "["+cindex+"]");
		
						$(this).attr("name", newtask);
		
						var mytask	= $(this).attr('onblur');
						var tasktb	= mytask.replace(/(\d+)/g, cindex);
						$(this).attr('onblur', tasktb);
		
						$("#"+this.id).live('blur', function() {
		
							var curid	= this.id.split('_')[1];
		
							hideTaskDetailsTextBox(curid);
						});
					}
		
					if (!((fstr == 'break_hours') || (fstr.indexOf("daily_rate") > -1) || (fstr == 'addtaskdetails') || (fstr == 'textlabel') || (fstr == 'taskTB') || (fstr == 'span')))
					{
						this.tabIndex	= tabindexcount++;
					}
				}
			}
		});
		
		$trLast.after($clone);
		
		//calling the new function for binding new UI
		$trLast.find('.select2-select').select2({minimumResultsForSearch: -1}); // Re-instrument original row

		$clone.find('.select2-select').select2({minimumResultsForSearch: -1}); // Instrument clone
		
		$("#issues").attr('tabindex',tabindexcount++);
		$("#timefile").attr('tabindex',tabindexcount++);
		$("#tabindexcount").val(parseInt(tabindexcount)-1);
		
		var dynrowcount	= $("#dynrowcount").val();
		$('#dynrowcount').val(parseInt(dynrowcount)+1);
		
		chainNavigation(cindex);
		
		//INSERT THE NEW ROWIDS TO THE rowid_hours_for_each_list JSON OBJECT
		if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {
				var new_rowid = "";
				var new_obj = {};
				new_rowid = $("#new_rowids").val();
				if(new_rowid != "")
				{
					$("#new_rowids").val("");
					$("#new_rowids").val($("#new_rowids").val()+","+cindex);
					
				}else{
					
					$("#new_rowids").val(cindex);
				}
				
				new_rowids = $("#new_rowids").val();
				
				if(new_rowids != ""){
					
					var new_rowid_list = new_rowids.split(',');
					if(new_rowid_list.length > 0){
						
						for(var i=0; i<new_rowid_list.length; i++){
						
							new_rowid = new_rowid_list[i];
							new_obj[new_rowid_list[i]] = '';
							$.extend( true, rowid_hours_for_each_list, new_obj );
						}
						
					}
				}
		
		}
	}
	
	//for setting the positions of calenders
	$("#tcalico_0, #tcalico_1").click(function(){
		$("#tcal").css("position","fixed");
		$("#tcalShade").css("position","fixed");
	});
});
function delete_row(del_row_id)
{
	var i	= 0;
	var dayhrs	= 0;
	
	var splitid = del_row_id.split('_');
	var rowid = splitid.pop();
	$('#check_'+rowid).attr('checked', true);// checking the hidden check box which is going to delete

	var totinputs		= $("#MainTable input.chremove[type=checkbox]").length;
	var totcheckedInputs	= $("#MainTable input.chremove[type=checkbox]:checked").length;

	if (totcheckedInputs!=0) {

		if (parseInt(totinputs) == parseInt(totcheckedInputs)) {

			alert("Your timesheets must have atleast one entry. You can't delete all the entries.");
			return false;

		} else {

			$('#MainTable input.chremove[type=checkbox]').each(function() {

				if (this.checked) {

					id	= this.id;
					splitid	= id.split('_');
					cur_chk	= splitid.pop(-1);

					if ($("#daily_rate_0_"+cur_chk).val() != "") {

						total_hours_val	= parseFloat($("#daily_rate_0_"+cur_chk).val());
						dayhrs	= dayhrs + total_hours_val;
					}

					// START - SEVENTH DAY RULE SPECIFIC
					if (seventhdayrule_flag != 0) {
						removeRowId(cur_chk);
					}
					// END - SEVENTH DAY RULE SPECIFIC

					$("tr#row_"+cur_chk).remove();
					
					//TWO ADD DELETED ROWIDS IN deleted_rowids HDDEN ELEMENT
					if (ruletype_flag == "weekrule") {
					
						deleted_rowid = $("#deleted_rowids").val();
						if(deleted_rowid != "")
						{
							$("#deleted_rowids").val($("#deleted_rowids").val()+","+cur_chk);
						}else{
							$("#deleted_rowids").val(cur_chk);
						}
					}
				}

				i++;
			});

			if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {

				calculateweekhours();

			} else {

				calculateregularhours(cur_chk);
			}
		}

	} else {

		alert("You have to select atleast one timesheet entry to delete from the available list.");
		return false;
	}

	chainNavigation(rowid);
}
	
if (mode != 'edit') {

	/* Preventing user to close browser, if the form has any values to submit */
	$(window).on('beforeunload', function() {
		
		var flag_temp	= false;
	
		$(".rowRegularHours").each(function() {

			var id	= $(this).attr("id").split("_").pop(-1);

			pre_intime	= document.getElementById("pre_intime_" + id).value;
			pre_outtime	= document.getElementById("pre_outtime_" + id).value;
			post_intime	= document.getElementById("post_intime_" + id).value;
			post_outtime	= document.getElementById("post_outtime_" + id).value;
		
			if ((pre_intime != "" && pre_intime != "HH:MM AM") || (pre_outtime != ""  && pre_outtime != "HH:MM AM") || (post_intime != "" && post_intime != "HH:MM AM")|| (post_outtime != "" && post_outtime != "HH:MM AM")) {
				
				flag_temp = true;				
				return;
			}
		});
	
		if (flag_temp) {			
			
				if (module != 'MyProfile') {		
					if (mode != 'edit'){				
						var empacc_old = $("#empnames_oldvalue").val();
						$(".employees").val(empacc_old);
					}
				}				
		
	
			if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) {
				
				e = window.event || e;
								
				e.returnValue	= 'There is data keyed into the form that is not submitted.\nClick on "Leave this " to close the window without saving form. \nCick on "Stay on this page" to go back and save the form.';				
	
			} else {

				return 'There is data keyed into the form that is not submitted.\nClick on "Leave this " to close the window without saving form. \nCick on "Stay on this page" to go back and save the form.';
			}
		}
	});
	
}

//To Handle issue in IE where popup appears for every link even for submit and cancel also
(function($) {
   $(document).ready(function() {
 
       $('a').filter(function() {
           return (/^javascript\:/i).test($(this).attr('href'));
       }).each(function() {
           var hrefscript = $(this).attr('href');
           hrefscript = hrefscript.substr(11);
           $(this).data('hrefscript', hrefscript);
       }).click(function() {
           var hrefscript = $(this).data('hrefscript');
           eval(hrefscript);
           return false;
       }).attr('href', '#');
 
   });   
})(jQuery);

function getEmp() {

	var form	= document.sheet;

	form.action	= "/include/timeintimeout.php?module="+ module;

	form.submit();
}

function validateTime(fld_value) {

	if (fld_value == "" || fld_value.indexOf(":") < 0) {

		return false;

	} else {

		var sMinutes	= "";
		var sHours		= fld_value.split(':')[0];
		var tMinutes	= fld_value.split(':')[1];

		if (fld_value.substring(6).toUpperCase() == "AM" || fld_value.substring(6).toUpperCase() == "PM")
		sMinutes	= tMinutes.split(" ")[0];

		if (sHours == "" || isNaN(sHours) || parseInt(sHours) > 23) {

			return false;
		}

		if (sMinutes == "" || isNaN(sMinutes) || parseInt(sMinutes) > 59) {

			return false;
		}
	}

	return true;
}

//Function used to calculate the hours when entering the times in onchange event
function calculateTime(inout_rowid) {

	var rowid	= inout_rowid.split("_").pop(-1);

	var pre_inflag		= false;
	var pre_outflag		= false;
	var post_inflag		= false;
	var post_outflag	= false;

	var sel_date		= $("#daily_dates_" + rowid).val();
	var pre_intime		= $("#pre_intime_" + rowid).val();
	var pre_outtime		= $("#pre_outtime_" + rowid).val();
	var post_intime		= $("#post_intime_" + rowid).val();
	var post_outtime	= $("#post_outtime_" + rowid).val();

	var tot_pre_hours	= 0.00;
	var tot_post_hours	= 0.00;

	if (pre_outtime == "" || post_intime == "") {

		$("#break_hours_" + rowid).val("");
	}

	if ((pre_intime == "" && pre_outtime == "" && post_intime == "" && post_outtime == "") ||
	    (pre_intime != "" && pre_outtime == "") ||
	    (post_intime != "" && post_outtime == "") ||
	    (pre_intime == "" && pre_outtime != "") ||
	    (post_intime == "" && post_outtime != "")) {

		$("#daily_rate_0_" + rowid).val("");
		$("#daily_rate_1_" + rowid).val("");
		$("#daily_rate_2_" + rowid).val("");
		$("#singledaytotalhrs_" + rowid).val("");

		if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {

			calculateweekhours();

		} else {

			calculateregularhours(rowid);
		}
	}
	//validating the each time in and time out fields
	if (pre_intime != "") {

		pre_inflag	= validateTime(pre_intime);

		if (!pre_inflag) {

			alert("Invalid Time-In. Please enter valid time.");

			$("#pre_intime_" + rowid).val("");

			setTimeout(function() {
				$("#pre_intime_" + rowid).focus();
			}, 0);

			return false;
		}
	}

	if (pre_outtime != "") {

		pre_outflag	= validateTime(pre_outtime);

		if (!pre_outflag) {

			alert("Invalid Time-Out. Please enter valid time.");

			$("#pre_outtime_" + rowid).val("");

			setTimeout(function() {
				$("#pre_outtime_" + rowid).focus();
			}, 0);

			return false;
		}
	}

	if (post_intime != "") {

		post_inflag	= validateTime(post_intime);

		if (!post_inflag) {

			alert("Invalid Time-In. Please enter valid time.");

			$("#break_hours_" + rowid).val("");
			$("#post_intime_" + rowid).val("");

			setTimeout(function() {
				$("#post_intime_" + rowid).focus();
			}, 0);

			return false;
		}
	}

	if (post_outtime != "") {

		post_outflag	= validateTime(post_outtime);

		if (!post_outflag) {

			alert("Invalid Time-Out. Please enter valid time.");

			$("#break_hours_" + rowid).val("");
			$("#post_outtime_" + rowid).val("");

			setTimeout(function() {
				$("#post_outtime_" + rowid).focus();
			}, 0);

			return false;
		}
	}
	//End- Validations for time in and time out fields

	if ((pre_inflag && pre_outflag) || (post_inflag && post_outflag)) {

		if (pre_inflag && pre_outflag) {

			if (pre_intime.substring(6).toUpperCase() == "PM" && pre_outtime.substring(6).toUpperCase() == "AM") {

				alert("Please enter Time-In & Time-Out for the same day");

				$("#pre_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#pre_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			var pre_in_str	= sel_date + " " + pre_intime;
			var pre_out_str	= sel_date + " " + pre_outtime;

			var pre_start_time	= Date.parse(pre_in_str);
			var pre_end_time	= Date.parse(pre_out_str);

			if (pre_end_time <= pre_start_time) {

				alert("Out-Time should be greater than In-Time");

				$("#pre_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#pre_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			// Rounds the time based on time increment
			if (time_increment !== "0") {
				pre_in_str	 = sel_date+" " + pre_intime;	
				pre_out_str	 = sel_date+" " + pre_outtime;

				pre_start_time	= Date.parse(pre_in_str);
				pre_end_time	= Date.parse(pre_out_str);
			}
			// Block ends here

			tot_pre_hours	= calculateTimeDifference(pre_start_time, pre_end_time);
		}

		if (pre_inflag && pre_outflag && post_inflag) {

			var str_pre_in	= sel_date + " " + pre_intime;
			var str_pre_out	= sel_date + " " + pre_outtime;
			var str_post_in	= sel_date + " " + post_intime;

			var pre_stime	= Date.parse(str_pre_in);
			var pre_etime	= Date.parse(str_pre_out);
			var post_stime	= Date.parse(str_post_in);

			if (post_stime <= pre_etime) {

				alert("Post Time-In should be greater than Pre Time-Out ");

				$("#break_hours_" + rowid).val("");
				$("#post_intime_" + rowid).val("");

				setTimeout(function() {
					$("#post_intime_" + rowid).focus();
				}, 0);

				$("#post_intime_" + rowid).bind('focus', function() {
					$(this).select();
				});
				
				// Rounds the time based on time increment
				if (time_increment !== "0") {
					str_pre_in	 = sel_date+" "+pre_intime;	
					str_pre_out	 = sel_date+" "+pre_outtime;
					
					pre_stime	= Date.parse(str_pre_in);
					pre_etime	= Date.parse(str_pre_out);
				}
				// Block ends here

				tot_pre_hours	= calculateTimeDifference(pre_stime, pre_etime);
				total_hours	= parseFloat(tot_pre_hours);
				total_hours	= formatNumber(total_hours);

				$("#daily_rate_0_" + rowid).val(total_hours);
				$("#daytotalhrs_" + rowid).val(total_hours);
				$("#singledaytotalhrs_" + rowid).val(total_hours);

				if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {

					calculateweekhours();

				} else {

					calculateregularhours(rowid);
				}

				return false;
			}

			if (post_stime >= pre_stime && post_stime <= pre_etime) {

				alert("Time-In hours are overlapped. Please re-enter the Time-In hours.");

				$("#post_intime_" + rowid).val("");
				$("#break_hours_" + rowid).val("");

				setTimeout(function() {
					$("#post_intime_" + rowid).focus();
				}, 0);

				// Rounds the time based on time increment
				if (time_increment !== "0") {
					str_pre_in	 = sel_date+" "+pre_intime;	
					str_pre_out	 = sel_date+" "+pre_outtime;

					pre_stime	= Date.parse(str_pre_in);
					pre_etime	= Date.parse(str_pre_out);
				}
				// Block ends here

				tot_pre_hours	= calculateTimeDifference(pre_stime, pre_etime);
				total_hours	= parseFloat(tot_pre_hours);
				total_hours	= formatNumber(total_hours);

				$("#daily_rate_0_" + rowid).val(total_hours);
				$("#daytotalhrs_" + rowid).val(total_hours);
				$("#singledaytotalhrs_" + rowid).val(total_hours);

				if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {

					calculateweekhours();

				} else {

					calculateregularhours(rowid);
				}

				return false;
			}

			var break_hours	= calculateBreakHours(pre_etime, post_stime);

			$("#break_hours_" + rowid).val(break_hours);
		}

		if (post_inflag && post_outflag) {

			if (post_intime.substring(6).toUpperCase() == "PM" && post_outtime.substring(6).toUpperCase() == "AM") {

				alert("Please enter Time-In & Time-Out for the same day");

				$("#post_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#post_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			var post_in_str		= sel_date + " " + post_intime;
			var post_out_str	= sel_date + " " + post_outtime;

			var post_start_time	= Date.parse(post_in_str);
			var post_end_time	= Date.parse(post_out_str);

			if (post_end_time <= post_start_time) {

				alert("Out-Time should be greater than In-Time");

				$("#post_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#post_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			// Rounds the time based on time increment
			//if (rounding_pref === "1" && time_increment != "" && !isNaN(time_increment) && time_increment !== "0") {
			if (time_increment !== "0") {
				post_in_str	 = sel_date+"  "+post_intime;	
				post_out_str	 = sel_date+"  "+post_outtime;

				post_start_time	= Date.parse(post_in_str);
				post_end_time	= Date.parse(post_out_str);
			}
			// Block ends here

			tot_post_hours	= calculateTimeDifference(post_start_time, post_end_time);
		}

		total_hours	= parseFloat(tot_pre_hours) + parseFloat(tot_post_hours);
		total_hours	= formatNumber(total_hours);

		$("#daily_rate_0_" + rowid).val(total_hours);
		$("#daytotalhrs_" + rowid).val(total_hours);
		$("#singledaytotalhrs_" + rowid).val(total_hours);

		if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {

			calculateweekhours();

		} else {

			calculateregularhours(rowid);
		}

	} else {

		$("#daily_rate_0_" + rowid).val("");
		$("#daily_rate_1_" + rowid).val("");
		$("#daily_rate_2_" + rowid).val("");
		$("#singledaytotalhrs_" + rowid).val("");
	}

	// SEVENTH DAY RULE SPECIFIC
	if (seventhdayrule_flag != 0) {

		if (pos_weekstartday >= 0 && pos_weekendday <= 6) {

			apply_rule(rowid);
		}
	}
}

// SEVENTH DAY RULE SPECIFIC
function apply_rule(rowid) {
	
	//Check the state of selected assignments before apply the rule.
	rule_condition3 = chk_state_of_asgn();
	
	// form a set of unique days
	if((findInArray(dyna_dates,$("#daily_dates_" + rowid).val(), 1) == false) || dyna_dates.length == 0){
		
		dyna_dates.push([rowid, $("#daily_dates_" + rowid).val(), parseDate($("#daily_dates_" + rowid).val()).getDay()]);
					
		// if no. of days in set = 6 or more
		if (dyna_dates.length >= days_to_check || dyna_dates.length >= 6){
		
			// Order the elements in the set
			dyna_dates.sort(SortByDate);
			
			dyna_rowid = "";
			dyna_list  = "";
			
			// Create a string of all unique seven days
			for (var i = 0; i < dyna_dates.length; i++) {
				var dyna_key 	= dyna_dates[i][0];
				var dyna_value 	= dyna_dates[i][1];
				var dyna_day	= dyna_dates[i][2];
				dyna_rowid 	+= dyna_key+"#";
				dyna_list 	+= dyna_value+"#";
			}
			
			// removes # from the end of the string
			dyna_list = dyna_list.slice(0, - 1);
			dyna_rowid = dyna_rowid.slice(0, - 1);				
						
			if ((dyna_list.indexOf(service_list) > -1 || dyna_list.indexOf(service_sublist) > -1) && pos_weekstartday > -1)  {
				rule_condition1 = true;
			}
		}
	}

	// Use Case : When all fields cleared	
	var rule_pre_intime	= $("#pre_intime_" + rowid).val();
	var rule_pre_outtime	= $("#pre_outtime_" + rowid).val();
	var rule_post_intime	= $("#post_intime_" + rowid).val();
	var rule_post_outtime	= $("#post_outtime_" + rowid).val();

	if ((findInArray(dyna_dates,$("#daily_dates_" + rowid).val(), 1) == true) &&
	    ((rule_pre_intime == "" && rule_pre_outtime == "" && rule_post_intime == "" && rule_post_outtime == "") ||
	     (rule_pre_intime != "" && rule_pre_outtime == "" && rule_post_intime == "" && rule_post_outtime == "") ||
	     (rule_pre_intime == "" && rule_pre_outtime != "" && rule_post_intime == "" && rule_post_outtime == "") ||
	     (rule_pre_intime == "" && rule_pre_outtime == "" && rule_post_intime != "" && rule_post_outtime == "") ||
	     (rule_pre_intime == "" && rule_pre_outtime == "" && rule_post_intime == "" && rule_post_outtime != "") ||
	     (rule_pre_intime == "" && rule_pre_outtime != "" && rule_post_intime != "" && rule_post_outtime == "") ||
	     (rule_pre_intime != "" && rule_pre_outtime == "" && rule_post_intime == "" && rule_post_outtime != ""))
	    )
	{
		removeRowId(rowid);
	}

	if (dyna_dates.length < (days_to_check-1)){	
		rule_condition1 = false;
	}
	
	if (rule_condition1 == true) {
		
		var new_length  = -1;
		
		
		if (dyna_dates.length >= (days_to_check-1)) {
			new_length = dyna_dates.length-1;
		}
		
		if (dyna_dates.length >= days_to_check) {
			new_length = dyna_dates.length-2;
		}
						
		
		// Create a string of all unique six/seven days
		for (var i = 0; i < new_length; i++) {
			rule_condition2 = true;
			var dyna_key = dyna_dates[i][0];
			var dyna_value = dyna_dates[i][1];			
		}
		
		seventh_day_rowid = find_sixth_seventh_day_indexes(seventhdayrule_weekendday);			
	
		if (default_weekday.indexOf(seventhdayrule_weekendday) == 0) {
			sixth_day_name = default_weekday[6];
		}
		if (default_weekday.indexOf(seventhdayrule_weekendday) > 0) {
			sixth_day_name = default_weekday[default_weekday.indexOf(seventhdayrule_weekendday)-1];
		}
		
		sixth_day_rowid = find_sixth_seventh_day_indexes(sixth_day_name);			
	}
	
	if(rule_condition1 == true && rule_condition2 == true && dyna_dates.length > 5 && rule_condition3==true) {
		
		//message_seventh_day_rule = "Seventh Day Rule Applicable: Hours entered on 6th Day ("+sixth_day_name+") and 7th Day ("+seventhdayrule_weekendday+") will be considered under Overtime and Doubletime.";
		//$("#seventh_day_rule_var").val(message_seventh_day_rule);
						
		sixth_day_regular_new = 0.00;
		seventh_day_regular_new = 0.00;
		
		// Conditions to check
		// 1st Time Applying Sixth Day Rule >> Regular Hours - Not Empty		
		if (($("#daily_rate_0_" + sixth_day_rowid).val() != '' && $("#daily_rate_0_" + sixth_day_rowid).val() != 0.00  && $("#daily_rate_0_" + sixth_day_rowid).val() != 0) ||  ($("#daily_rate_0_" + sixth_day_rowid).val() == 0.00  && $("#daily_rate_1_" + sixth_day_rowid).val() != 0.00)){						
			total_reg_hours = getTotalRegularHours(dyna_rowid, sixth_day_rowid, seventh_day_rowid, sixth_day_rowid);

			if (total_reg_hours != 0) {

				//Get Total Hours for sixth day			
				sixth_day_reg_ot_dt = parseFloat(formatNumber($("#singledaytotalhrs_" + sixth_day_rowid).val()));

				// Get recalculated regular hours
				if (seventhdayrule_weekmaxregular > total_reg_hours) {
					sixth_day_regular_new = parseFloat(seventhdayrule_weekmaxregular)-parseFloat(total_reg_hours);
				}else{
					sixth_day_regular_new = parseFloat(total_reg_hours)-parseFloat(seventhdayrule_weekmaxregular);
				}

				if (sixth_day_reg_ot_dt >= max_reg_hrs && sixth_day_regular_new >= max_reg_hrs) {					
					sixth_day_regular_new = max_reg_hrs;
				}else if(sixth_day_reg_ot_dt < sixth_day_regular_new){
					sixth_day_regular_new = sixth_day_reg_ot_dt;
				}

				$("#daily_rate_0_" + sixth_day_rowid).val(formatNumber(sixth_day_regular_new));
				
				// Get remaining hours			
				sixth_day_reg_ot_dt_remaining = parseFloat(sixth_day_reg_ot_dt) - parseFloat(sixth_day_regular_new);			
				sixth_day_overtime = sixth_day_reg_ot_dt_remaining;				

				if (total_reg_hours < (max_reg_hrs+max_ovt_hrs) && dyna_dates.length == 7) {
					sixth_day_max_overtime_temp = parseFloat(sixth_day_max_overtime) - parseFloat(sixth_day_regular_new);
				}else{
					sixth_day_max_overtime_temp = sixth_day_max_overtime;
				}

				if (sixth_day_overtime <= sixth_day_max_overtime_temp) {				

					if (sixth_day_overtime != 0 && sixth_day_overtime != 0.00){
						//Rounding Code
					var sixth_day_overtime = sixth_day_overtime.toFixed(2);
					var sixday_otime = sixth_day_overtime.split(".");
					if(sixday_otime[1] =='' || sixday_otime[1] == undefined){
						sixday_otime[1]=0;
					}
					var sixtday_omint = '0.'+sixday_otime[1];
					sixtday_omin = getRoundOffMinutes_time(sixtday_omint, time_increment);
					sixthday_ofinal = parseFloat(sixday_otime[0]) + parseFloat(sixtday_omin);
					//Code Ends		
					$("#daily_rate_1_" + sixth_day_rowid).val(formatNumber(sixthday_ofinal));
					$("#daily_rate_2_" + sixth_day_rowid).val("");
					}else{
					$("#daily_rate_1_" + sixth_day_rowid).val("");

					$("#daily_rate_2_" + sixth_day_rowid).val("");
					}
				}else{
					sixth_day_doubletime 	= parseFloat(sixth_day_overtime) - parseFloat(sixth_day_max_overtime_temp);
					sixth_day_overtime 	= sixth_day_max_overtime_temp;		
					//Rounding Code
					var sixth_day_overtime = sixth_day_overtime.toFixed(2);
					var sixday_otime = sixth_day_overtime.split(".");
					if(sixday_otime[1] =='' || sixday_otime[1] == undefined){
						sixday_otime[1]=0;
					}
					var sixtday_omint = '0.'+sixday_otime[1];
					sixtday_omin = getRoundOffMinutes_time(sixtday_omint, time_increment);
					sixthday_ofinal = parseFloat(sixday_otime[0]) + parseFloat(sixtday_omin);
					//Code Ends					
					$("#daily_rate_1_" + sixth_day_rowid).val(formatNumber(sixthday_ofinal));
					//Rounding Code
					var sixth_day_doubletime = sixth_day_doubletime.toFixed(2);
					var sixday_time = sixth_day_doubletime.split(".");
					if(sixday_time[1] =='' || sixday_time[1] == undefined){
						sixday_time[1]=0;
					}
					var sixtday_mint = '0.'+sixday_time[1];
					sixtday_min = getRoundOffMinutes_time(sixtday_mint, time_increment);
					sixthday_final = parseFloat(sixday_time[0]) + parseFloat(sixtday_min);
					//Code Ends
					$("#daily_rate_2_" + sixth_day_rowid).val(formatNumber(sixthday_final));
				}
			}
		}
		
		// Conditions to check
		// 1st Time Applying Seventh Day Rule >> Regular Hours - Not Empty
		
		if (($("#daily_rate_0_" + seventh_day_rowid).val() != '' && $("#daily_rate_0_" + seventh_day_rowid).val() != 0.00  && $("#daily_rate_0_" + seventh_day_rowid).val() != 0) || ($("#daily_rate_0_" + seventh_day_rowid).val() == 0.00  && $("#daily_rate_1_" + seventh_day_rowid).val() != 0.00)){
		
			total_reg_hours = getTotalRegularHours(dyna_rowid, sixth_day_rowid, seventh_day_rowid, seventh_day_rowid);

			if (total_reg_hours != 0) {

				// Get Total Hours for seventh day							
				seventh_day_reg_ot_dt = parseFloat(formatNumber($("#singledaytotalhrs_" + seventh_day_rowid).val()));

				// Get recalculated regular hours
				// Use Case: Where Mon-Sat => Total Regular Hours < seventhdayrule_weekmaxregular. So, every hour worked on Sunday will go to OT and DT.
				if (total_reg_hours < seventhdayrule_weekmaxregular && dyna_dates.length == 7) {
					seventh_day_regular_new = 0;
				}
				else if (seventhdayrule_weekmaxregular > total_reg_hours) {
					seventh_day_regular_new = parseFloat(seventhdayrule_weekmaxregular)-parseFloat(total_reg_hours);
				}else{
					seventh_day_regular_new = parseFloat(total_reg_hours)-parseFloat(seventhdayrule_weekmaxregular);
				}
				
				if (seventh_day_reg_ot_dt >= max_reg_hrs && seventh_day_regular_new >= max_reg_hrs) {					
					seventh_day_regular_new = max_reg_hrs;
				}else if(seventh_day_reg_ot_dt < seventh_day_regular_new){
					seventh_day_regular_new = seventh_day_reg_ot_dt;
				}
				seventh_day_regular_new=seventh_day_regular_new.toFixed(2);
				$("#daily_rate_0_" + seventh_day_rowid).val(seventh_day_regular_new);				

				// Get remaining hours
				seventh_day_reg_ot_dt_remaining = parseFloat(seventh_day_reg_ot_dt) - parseFloat(seventh_day_regular_new);			
				seventh_day_overtime = seventh_day_reg_ot_dt_remaining;

				if (total_reg_hours < (max_reg_hrs+max_ovt_hrs) && dyna_dates.length == 7) {					
					seventh_day_max_overtime_temp = parseFloat(seventh_day_max_overtime) - parseFloat(seventh_day_regular_new);
				}else{
					seventh_day_max_overtime_temp = (parseFloat(max_reg_hrs)+parseFloat(max_ovt_hrs));
				}

				if (seventh_day_overtime <= seventh_day_max_overtime_temp) {

					if (seventh_day_overtime != 0 && seventh_day_overtime != 0.00){
						//Rounding Code
					var seventh_day_overtime = seventh_day_overtime.toFixed(2);
					var seventh_otime = seventh_day_overtime.split(".");
					if(seventh_otime[1] =='' || seventh_otime[1] == undefined){
						seventh_otime[1]=0;
					}
					var seventh_omint = '0.'+seventh_otime[1];
					seventh_omin = getRoundOffMinutes_time(seventh_omint, time_increment);
					seventh_ofinal = parseFloat(seventh_otime[0]) + parseFloat(seventh_omin);
					//Code Ends		
					$("#daily_rate_1_" + seventh_day_rowid).val(formatNumber(seventh_ofinal));
					}else{
						$("#daily_rate_1_" + seventh_day_rowid).val("");

					$("#daily_rate_2_" + seventh_day_rowid).val("");
					}
				}else{
					seventh_day_doubletime 	= parseFloat(seventh_day_overtime) - seventh_day_max_overtime_temp;
					seventh_day_overtime 	= seventh_day_max_overtime_temp;					
					//Rounding Code
					var seventh_day_overtime = seventh_day_overtime.toFixed(2);
					var seventh_otime = seventh_day_overtime.split(".");
					if(seventh_otime[1] =='' || seventh_otime[1] == undefined){
						seventh_otime[1]=0;
					}
					var seventh_omint = '0.'+seventh_otime[1];
					seventh_omin = getRoundOffMinutes_time(seventh_omint, time_increment);
					seventh_ofinal = parseFloat(seventh_otime[0]) + parseFloat(seventh_omin);
					//Code Ends		
					$("#daily_rate_1_" + seventh_day_rowid).val(formatNumber(seventh_ofinal));
					//Rounding Code
					var seventh_day_doubletime = seventh_day_doubletime.toFixed(2);
					var sdd_time = seventh_day_doubletime.split(".");
					if(sdd_time[1] =='' || sdd_time[1] == undefined){
						sdd_time[1]=0;
					}
					var sdd_mint = '0.'+sdd_time[1];
					sdd_min = getRoundOffMinutes_time(sdd_mint, time_increment);
					sdd_final = parseFloat(sdd_time[0]) + parseFloat(sdd_min);
					//Code Ends
					$("#daily_rate_2_" + seventh_day_rowid).val(formatNumber(sdd_final));
				}
			}
		}			
		recalculateregularhours(rowid);
	}
	
	// Executes when non-consecutive days found in a workweek
	if (areNonConsecutiveDaysPresent(dyna_rowid, sixth_day_rowid, seventh_day_rowid) == true && rule_condition3==true) {
		
		//message_overtime_rule = "Overtime Rules Applicable";
		//$("#seventh_day_rule_var").val(message_overtime_rule);
		
		sixth_day_regular_new = 0.00;
		seventh_day_regular_new = 0.00;		
				
		// Conditions to check
		// 1st Time Applying Sixth Day Rule >> Regular Hours OR Field Not Empty					
		if (($("#daily_rate_0_" + sixth_day_rowid).val() != '' && $("#daily_rate_0_" + sixth_day_rowid).val() != 0.00  && $("#daily_rate_0_" + sixth_day_rowid).val() != 0) ||  ($("#daily_rate_0_" + sixth_day_rowid).val() == 0.00  && $("#daily_rate_1_" + sixth_day_rowid).val() != 0.00)){						
			total_reg_hours = getTotalRegularHours(dyna_rowid, sixth_day_rowid, seventh_day_rowid, sixth_day_rowid);			
			if (total_reg_hours != 0) {

				//Get Total Hours for sixth day
				sixth_day_reg_ot_dt = parseFloat(formatNumber($("#singledaytotalhrs_" + sixth_day_rowid).val()));				
				// Get recalculated regular hours
				if (seventhdayrule_weekmaxregular > total_reg_hours) {
					sixth_day_regular_new = parseFloat(seventhdayrule_weekmaxregular)-parseFloat(total_reg_hours);
				}else{
					sixth_day_regular_new = parseFloat(total_reg_hours)-parseFloat(seventhdayrule_weekmaxregular);
				}

				if (sixth_day_reg_ot_dt >= max_reg_hrs && sixth_day_regular_new >= max_reg_hrs) {					
					sixth_day_regular_new = max_reg_hrs;
				}else if(sixth_day_reg_ot_dt < sixth_day_regular_new){
					sixth_day_regular_new = sixth_day_reg_ot_dt;
				}

				$("#daily_rate_0_" + sixth_day_rowid).val(formatNumber(sixth_day_regular_new));

				// Get remaining hours
				sixth_day_reg_ot_dt_remaining = parseFloat(sixth_day_reg_ot_dt) - parseFloat(sixth_day_regular_new);			
				sixth_day_overtime = sixth_day_reg_ot_dt_remaining;

				sixth_day_max_overtime_temp = parseFloat(sixth_day_max_overtime) - parseFloat(sixth_day_regular_new);

				if (sixth_day_overtime <= sixth_day_max_overtime_temp) {

					if (sixth_day_overtime != 0 && sixth_day_overtime != 0.00){

						//Rounding Code
						var sixth_day_overtime = sixth_day_overtime.toFixed(2);
						var sixday_otime = sixth_day_overtime.split(".");
						if(sixday_otime[1] =='' || sixday_otime[1] == undefined){
							sixday_otime[1]=0;
						}
						var sixtday_omint = '0.'+sixday_otime[1];
						sixtday_omin = getRoundOffMinutes_time(sixtday_omint, time_increment);
						sixthday_ofinal = parseFloat(sixday_otime[0]) + parseFloat(sixtday_omin);
						//Code Ends
						
						$("#daily_rate_1_" + sixth_day_rowid).val(formatNumber(sixthday_ofinal));
					}else{
						$("#daily_rate_1_" + sixth_day_rowid).val("");
	
						$("#daily_rate_2_" + sixth_day_rowid).val("");
					}
				}else{
					sixth_day_doubletime 	= parseFloat(sixth_day_overtime) - sixth_day_max_overtime_temp;
					sixth_day_overtime 	= sixth_day_max_overtime_temp;

					//Rounding Code
					var sixth_day_overtime = sixth_day_overtime.toFixed(2);
					var sixday_otime = sixth_day_overtime.split(".");
					if(sixday_otime[1] =='' || sixday_otime[1] == undefined){
						sixday_otime[1]=0;
					}
					var sixtday_omint = '0.'+sixday_otime[1];
					sixtday_omin = getRoundOffMinutes_time(sixtday_omint, time_increment);
					sixthday_ofinal = parseFloat(sixday_otime[0]) + parseFloat(sixtday_omin);
					//Code Ends

					$("#daily_rate_1_" + sixth_day_rowid).val(formatNumber(sixthday_ofinal));

					//Rounding Code
					var sixth_day_doubletime = sixth_day_doubletime.toFixed(2);
					var sixday_time = sixth_day_doubletime.split(".");
					if(sixday_time[1] =='' || sixday_time[1] == undefined){
						sixday_time[1]=0;
					}
					var sixtday_mint = '0.'+sixday_time[1];
					sixtday_min = getRoundOffMinutes_time(sixtday_mint, time_increment);
					sixthday_final = parseFloat(sixday_time[0]) + parseFloat(sixtday_min);
					//Code Ends

					$("#daily_rate_2_" + sixth_day_rowid).val(formatNumber(sixthday_final));
				}
			}
		}

		// Conditions to check
		// 1st Time Applying Seventh Day Rule >> Regular Hours OR Not Empty		
		if (($("#daily_rate_0_" + seventh_day_rowid).val() != '' && $("#daily_rate_0_" + seventh_day_rowid).val() != 0.00  && $("#daily_rate_0_" + seventh_day_rowid).val() != 0) || ($("#daily_rate_0_" + seventh_day_rowid).val() == 0.00  && $("#daily_rate_1_" + seventh_day_rowid).val() != 0.00)){

			total_reg_hours = getTotalRegularHours(dyna_rowid, sixth_day_rowid, seventh_day_rowid, seventh_day_rowid);
			
			if (total_reg_hours != 0) {

				// Get Total Hours for seventh day							
				seventh_day_reg_ot_dt = parseFloat(formatNumber($("#singledaytotalhrs_" + seventh_day_rowid).val()));				
				// Get recalculated regular hours
				if (seventhdayrule_weekmaxregular > total_reg_hours) {
					seventh_day_regular_new = parseFloat(seventhdayrule_weekmaxregular)-parseFloat(total_reg_hours);
				}else{
					seventh_day_regular_new = parseFloat(total_reg_hours)-parseFloat(seventhdayrule_weekmaxregular);
				}

				if (seventh_day_reg_ot_dt >= max_reg_hrs && seventh_day_regular_new >= max_reg_hrs) {					
					seventh_day_regular_new = max_reg_hrs;
				}else if(seventh_day_reg_ot_dt < seventh_day_regular_new){
					seventh_day_regular_new = seventh_day_reg_ot_dt;
				}
				seventh_day_regular_new=parseFloat(seventh_day_regular_new).toFixed(2);
				$("#daily_rate_0_" + seventh_day_rowid).val(seventh_day_regular_new);

				// Get remaining hours
				seventh_day_reg_ot_dt_remaining = parseFloat(seventh_day_reg_ot_dt) - parseFloat(seventh_day_regular_new);		
				seventh_day_overtime = seventh_day_reg_ot_dt_remaining;				

				seventh_day_max_overtime_temp = (parseFloat(max_reg_hrs)+parseFloat(max_ovt_hrs)) - parseFloat(seventh_day_regular_new)	

				if (seventh_day_overtime <= seventh_day_max_overtime_temp) {

					if (seventh_day_overtime != 0 && seventh_day_overtime != 0.00){
					$("#daily_rate_1_" + seventh_day_rowid).val(formatNumber(seventh_day_overtime));
					}else{
					$("#daily_rate_1_" + seventh_day_rowid).val("");

					$("#daily_rate_2_" + seventh_day_rowid).val("");
					}
				}else{
					seventh_day_doubletime 	= parseFloat(seventh_day_overtime) - seventh_day_max_overtime_temp;
					seventh_day_overtime 	= seventh_day_max_overtime_temp;				
					$("#daily_rate_1_" + seventh_day_rowid).val(formatNumber(seventh_day_overtime));
					$("#daily_rate_2_" + seventh_day_rowid).val(formatNumber(seventh_day_doubletime));
				}
			}
		}			
		recalculateregularhours(rowid);
	}
	
	// Rule Broken - seven consecutive days but daily regular hours and total regular hours (5 working days) conditions not met
	if(rule_condition1 == true && rule_condition2 != true){
		
		if (sixth_day_rowid > -1) {
			// Recalculate Sixth Day	
			reCalculateTime(sixth_day_rowid);
		}
		if (seventh_day_rowid > -1) {
			// Recalculate Seventh Day
			reCalculateTime(seventh_day_rowid);	
		}
	}
		
}

// Calculates Total Regular Hours within a workweek
function getTotalRegularHours(rowids, sixth_rowid, seventh_rowid, cur_rowid){	
	var specific_rowids = rowids.split("#");	
	total_reg_hours_temp = 0;
	for (var i = 0; i < specific_rowids.length; i++){		
		if (cur_rowid == sixth_rowid && specific_rowids[i] != sixth_rowid && specific_rowids[i] != seventh_rowid) {
			total_reg_hours_temp = total_reg_hours_temp + parseFloat(formatNumber($("#daily_rate_0_" + specific_rowids[i]).val()));
			if ($("#daily_rate_0_" + specific_rowids[i]).val() == '' && $("#singledaytotalhrs_" + specific_rowids[i]).val() != ''){
				return 0;
			}
		}
		if (cur_rowid == seventh_rowid && specific_rowids[i] != seventh_rowid) {
			total_reg_hours_temp = total_reg_hours_temp + parseFloat(formatNumber($("#daily_rate_0_" + specific_rowids[i]).val()));
			if ($("#daily_rate_0_" + specific_rowids[i]).val() == '' && $("#singledaytotalhrs_" + specific_rowids[i]).val() != ''){
				return 0;
			}
		}
	}
	return total_reg_hours_temp;
}

// SEVENTH DAY SPECIFIC: Finds whether consecutive days in a workweek or not.
function areNonConsecutiveDaysPresent(rowids, sixth_rowid, seventh_rowid){

	if (seventh_rowid < 0) {

		seventh_rowid = find_sixth_seventh_day_indexes(seventhdayrule_weekendday);	
		seventh_day_rowid   = seventh_rowid;
	}

	if (sixth_rowid < 0) {

		if (default_weekday.indexOf(seventhdayrule_weekendday) == 0) {
			sixth_day_name = default_weekday[6];
		}

		if (default_weekday.indexOf(seventhdayrule_weekendday) > 0) {
			sixth_day_name = default_weekday[default_weekday.indexOf(seventhdayrule_weekendday)-1];
		}

		sixth_rowid = find_sixth_seventh_day_indexes(sixth_day_name);
		//sixth_day_rowid = sixth_rowid;
	}

	if((findInArray(dyna_dates,sixth_rowid, 0) == true && findInArray(dyna_dates,seventh_rowid, 0) == true && dyna_dates.length < 7 ) || (findInArray(dyna_dates,sixth_rowid, 0) == true && dyna_dates.length < 7 ) || (findInArray(dyna_dates,seventh_rowid, 0) == true && dyna_dates.length < 7 )){
		//found_nonconsecutive_days = true;
		return true;
	}
	return false;
}

// SEVENTH DAY SPECIFIC: Finds sixth and seventh day's index
function find_sixth_seventh_day_indexes(curr_day){
	
	for (var i = 0; i < dyna_dates.length; i++) {		
		if(curr_day == default_weekday[dyna_dates[i][2]]){			
			return (dyna_dates[i][0]);
		}
	}
	return -1;
}

// SEVENTH DAY SPECIFIC: Removes element from dyna_dates array - list maintained for keeping dates selected by user
function removeRowId(del_rowid) {
	var update_dyna_rowid = "";
	var update_dyna_list = "";

	var working_day = parseDate($("#daily_dates_" + del_rowid).val()).getDay();
	for (var i = 0; i < dyna_dates.length; i++) {		
		var dyna_key = dyna_dates[i][0];
		var dyna_value = dyna_dates[i][1];			
			
		// deletes first element found
		if (dyna_key == del_rowid) {
			// Use Case: When date / assignment changed from dropdown. Assigns previously held value.
			working_day = dyna_dates[i][2]; 
			dyna_dates.splice(i,1);			
			break;
		}				
	}
	
	rule_condition3 = chk_state_of_asgn();

	// SEVENTH DAY RULE SPECIFIC	
	if (dyna_dates.length <= (days_to_check-1)){
		
		temp_rowid = -1;
		rule_condition1 = false;
				
		seventh_day_rowid = temp_rowid = find_sixth_seventh_day_indexes(seventhdayrule_weekendday);
		
		if (temp_rowid > -1 && temp_rowid != del_rowid) {				
			reCalculateTime(temp_rowid);				
		}
		if (default_weekday.indexOf(seventhdayrule_weekendday) == 0) {
			sixth_day = default_weekday[6];
		}
		if (default_weekday.indexOf(seventhdayrule_weekendday) > 0) {
			sixth_day = default_weekday[default_weekday.indexOf(seventhdayrule_weekendday)-1];
		}
		
		sixth_day_rowid = temp_rowid = find_sixth_seventh_day_indexes(sixth_day);
		
		if (temp_rowid > -1 && temp_rowid != del_rowid){			
			if (default_weekday[working_day] != sixth_day && default_weekday[working_day] != seventhdayrule_weekendday && findInArray(dyna_dates,working_day,2) == false) {
					reCalculateTime(temp_rowid);			
			}			
		}
		
		// Handles case where seventh day deleted and sixth day present and any other day regular hous >= 8 hours
		dyna_dates.sort(SortByDate);
				
		// Create a string of all unique six/seven days
		for (var i = 0; i < dyna_dates.length; i++) {
			update_dyna_rowid += dyna_dates[i][0]+"#";
			update_dyna_list += dyna_dates[i][1]+"#";
		}
		
		// removes # from the end of the string
		update_dyna_rowid = update_dyna_rowid.slice(0, - 1);	
		update_dyna_list = update_dyna_list.slice(0, - 1);
		
		if ((update_dyna_list.indexOf(service_list) < 0 && update_dyna_list.indexOf(service_sublist) > -1) && pos_weekstartday > -1)  {
				rule_condition1 = true;
		}
	}
	
	// Executes when non-consecutive days found in a workweek
	if (areNonConsecutiveDaysPresent(update_dyna_rowid, sixth_day_rowid, seventh_day_rowid) == true && rule_condition3 == true) {		
				
		//message_overtime_rule = "Overtime Rules Applicable";
		//$("#seventh_day_rule_var").val(message_overtime_rule);
		
		sixth_day_regular_new = 0.00;
		seventh_day_regular_new = 0.00;		
				
		// Conditions to check
		// 1st Time Applying Sixth Day Rule >> Regular Hours OR Not Empty					
		if (($("#daily_rate_0_" + sixth_day_rowid).val() != '' && $("#daily_rate_0_" + sixth_day_rowid).val() != 0.00  && $("#daily_rate_0_" + sixth_day_rowid).val() != 0) ||  ($("#daily_rate_0_" + sixth_day_rowid).val() == 0.00  && $("#daily_rate_1_" + sixth_day_rowid).val() != 0.00)){						
			total_reg_hours 	= getTotalRegularHours(update_dyna_rowid, sixth_day_rowid, seventh_day_rowid, sixth_day_rowid);		
			if (total_reg_hours != 0) {									
				
				//Get Total Hours for sixth day			
				sixth_day_reg_ot_dt = parseFloat(formatNumber($("#singledaytotalhrs_" + sixth_day_rowid).val()));				
				// Get recalculated regular hours
				if (seventhdayrule_weekmaxregular > total_reg_hours) {
					sixth_day_regular_new = parseFloat(seventhdayrule_weekmaxregular)-parseFloat(total_reg_hours);
				}else{
					sixth_day_regular_new = parseFloat(total_reg_hours)-parseFloat(seventhdayrule_weekmaxregular);
				}

				if (sixth_day_reg_ot_dt >= max_reg_hrs && sixth_day_regular_new >= max_reg_hrs) {					
					sixth_day_regular_new = max_reg_hrs;
				}else if(sixth_day_reg_ot_dt < sixth_day_regular_new){
					sixth_day_regular_new = sixth_day_reg_ot_dt;
				}
				//Rounding Code
				sixth_day_hours = formatNumber(sixth_day_regular_new);
					//var sixth_day_hours = sixth_day_regular_new.toFixed(2);
					var sixth_day_time = sixth_day_hours.split(".");
					if(sixth_day_time[1] =='' || sixth_day_time[1] == undefined){
						sixth_day_time[1]=0;
					}
					var sixth_day_mint = '0.'+sixth_day_time[1];
					sixth_day_min = getRoundOffMinutes_time(sixth_day_mint, time_increment);
					sixth_day_regular_new = parseFloat(sixth_day_time[0]) + parseFloat(sixth_day_min);
					//Code Ends
					
				$("#daily_rate_0_" + sixth_day_rowid).val(formatNumber(sixth_day_regular_new));
				
				// Get remaining hours			
				sixth_day_reg_ot_dt_remaining = parseFloat(sixth_day_reg_ot_dt) - parseFloat(sixth_day_regular_new);			
				sixth_day_overtime = sixth_day_reg_ot_dt_remaining;

				sixth_day_max_overtime_temp = parseFloat(sixth_day_max_overtime) - parseFloat(sixth_day_regular_new);
			
				if (sixth_day_overtime <= sixth_day_max_overtime_temp) {				
					$("#daily_rate_1_" + sixth_day_rowid).val(formatNumber(sixth_day_overtime));
					$("#daily_rate_2_" + sixth_day_rowid).val("");
				}else{					
					sixth_day_doubletime 	= parseFloat(sixth_day_overtime) - sixth_day_max_overtime_temp;
					sixth_day_overtime 	= sixth_day_max_overtime_temp;		
					//Rounding Code
					var sixth_day_overtime = sixth_day_overtime.toFixed(2);
					var sixday_otime = sixth_day_overtime.split(".");
					if(sixday_otime[1] =='' || sixday_otime[1] == undefined){
						sixday_otime[1]=0;
					}
					var sixtday_omint = '0.'+sixday_otime[1];
					sixtday_omin = getRoundOffMinutes_time(sixtday_omint, time_increment);
					sixthday_ofinal = parseFloat(sixday_otime[0]) + parseFloat(sixtday_omin);
					//Code Ends						
					$("#daily_rate_1_" + sixth_day_rowid).val(formatNumber(sixthday_ofinal));
					//Rounding Code
					var sixth_day_doubletime = sixth_day_doubletime.toFixed(2);
					var sixthday_time = sixth_day_doubletime.split(".");
					if(sixthday_time[1] =='' || sixthday_time[1] == undefined){
						sixthday_time[1]=0;
					}
					
					var sixthday_mint = '0.'+sixthday_time[1];
					sixthday_min = getRoundOffMinutes_time(sixthday_mint, time_increment);
					sixthday_final = parseFloat(sixthday_time[0]) + parseFloat(sixthday_min);
					//Code Ends
					$("#daily_rate_2_" + sixth_day_rowid).val(formatNumber(sixthday_final));
				}
			}
		}
		
		// Conditions to check
		// 1st Time Applying Seventh Day Rule >> Regular Hours OR Not Empty		
		if (($("#daily_rate_0_" + seventh_day_rowid).val() != '' && $("#daily_rate_0_" + seventh_day_rowid).val() != 0.00  && $("#daily_rate_0_" + seventh_day_rowid).val() != 0) || ($("#daily_rate_0_" + seventh_day_rowid).val() == 0.00  && $("#daily_rate_1_" + seventh_day_rowid).val() != 0.00)){
		
			total_reg_hours 	= getTotalRegularHours(update_dyna_rowid, sixth_day_rowid, seventh_day_rowid, seventh_day_rowid);
			
			if (total_reg_hours != 0) {
				
				// Get Total Hours for seventh day							
				seventh_day_reg_ot_dt = parseFloat(formatNumber($("#singledaytotalhrs_" + seventh_day_rowid).val()));				
				// Get recalculated regular hours
				if (seventhdayrule_weekmaxregular > total_reg_hours) {
					seventh_day_regular_new = parseFloat(seventhdayrule_weekmaxregular)-parseFloat(total_reg_hours);
				}else{
					seventh_day_regular_new = parseFloat(total_reg_hours)-parseFloat(seventhdayrule_weekmaxregular);
				}			
				
				if (seventh_day_reg_ot_dt >= max_reg_hrs && seventh_day_regular_new >= max_reg_hrs) {					
					seventh_day_regular_new = max_reg_hrs;
				}else if(seventh_day_reg_ot_dt < seventh_day_regular_new){
					seventh_day_regular_new = seventh_day_reg_ot_dt;
				}
                                seventh_day_regular_new = formatNumber(seventh_day_regular_new);
				$("#daily_rate_0_" + seventh_day_rowid).val(seventh_day_regular_new);
				
		
				// Get remaining hours			
				seventh_day_reg_ot_dt_remaining = parseFloat(seventh_day_reg_ot_dt) - parseFloat(seventh_day_regular_new);		
				seventh_day_overtime = seventh_day_reg_ot_dt_remaining;				

				seventh_day_max_overtime_temp = (parseFloat(max_reg_hrs)+parseFloat(max_ovt_hrs)) - parseFloat(seventh_day_regular_new)	

				if (seventh_day_overtime <= seventh_day_max_overtime_temp) {					
					//Rounding Code
					var seventh_day_overtime = seventh_day_overtime.toFixed(2);
					var seventh_otime = seventh_day_overtime.split(".");
					if(seventh_otime[1] =='' || seventh_otime[1] == undefined){
						seventh_otime[1]=0;
					}
					var seventh_omint = '0.'+seventh_otime[1];
					seventh_omin = getRoundOffMinutes_time(seventh_omint, time_increment);
					seventh_ofinal = parseFloat(seventh_otime[0]) + parseFloat(seventh_omin);
					//Code Ends		
					$("#daily_rate_1_" + seventh_day_rowid).val(formatNumber(seventh_ofinal));
					$("#daily_rate_2_" + seventh_day_rowid).val("");
				}else{					
					seventh_day_doubletime 	= parseFloat(seventh_day_overtime) - seventh_day_max_overtime_temp;
					seventh_day_overtime 	= seventh_day_max_overtime_temp;				
					$("#daily_rate_1_" + seventh_day_rowid).val(formatNumber(seventh_day_overtime));
					$("#daily_rate_2_" + seventh_day_rowid).val(formatNumber(seventh_day_doubletime));
				}
			}
		}			
		recalculateregularhours(del_rowid);
	}
	if(rule_condition3 == false){
		if (sixth_day_rowid > -1) {
			// Recalculate Sixth Day	
			reCalculateTime(sixth_day_rowid);
		}
		if (seventh_day_rowid > -1) {
			// Recalculate Seventh Day
			reCalculateTime(seventh_day_rowid);	
		}
	}
}

// Recalculate hours for Seventh Day/Sixth Day Rule
function recalculateregularhours(current_rowid)
{
	var reg_total	= 0.00;
	var ovt_total	= 0.00;
	var dbt_total	= 0.00;

	var total_hours		= 0.00;
	var regular_hours	= 0.00;
	var overtime_hours	= 0.00;
	var doubletime_hours	= 0.00;
	var day_total_hours	= 0.00;

	var row_id;
	var inputid;

	var fmt_reg_hrs;
	var fmt_ovt_hrs;
	var fmt_dbt_hrs;
	var fmt_tot_hrs;

	$(".rowRegularHours").each(function() {

		inputid	= $(this).attr("id");
		row_id	= inputid.split("_").pop(-1);
		
		// GETTING REGULARTIME TOTAL HOURS
		regular_hours = $("#daily_rate_0_" + row_id).val();
		regular_hours = formatNumber(regular_hours);
		if (regular_hours != '' && regular_hours != '0.00' && regular_hours != 0)
		{
			//Rounding Code
				var regular_hours_time = regular_hours.split(".");
				if(regular_hours_time[1] =='' || regular_hours_time[1] == undefined)
				{
					regular_hours_time[1]=0;
				}
				var regular_hours_mint = '0.'+regular_hours_time[1];
				regular_hours_min = getRoundOffMinutes_time(regular_hours_mint, time_increment);
				regular_hours = parseFloat(regular_hours_time[0]) + parseFloat(regular_hours_min);
				regular_hours = regular_hours.toFixed(2);
			//Code Ends
		
			$("#daily_rate_0_" + row_id).val(regular_hours);
			regular_hours = formatNumber(regular_hours);
			reg_total = parseFloat(reg_total) + parseFloat(regular_hours);
		}
		
		// GETTING OVERTIME TOTAL HOURS
		overtime_hours = $("#daily_rate_1_" + row_id).val();
		overtime_hours = formatNumber(overtime_hours);
		if(overtime_hours != '' && overtime_hours != '0.00' && overtime_hours != 0) 
		{
			// Rounding Code
				var overtime_split = overtime_hours.split(".");
				if(overtime_split[1] == '' || overtime_split[1] == undefined)
				{
					overtime_split[1]=0;
				}	
				var overtime_min = '0.'+overtime_split[1];
				overtime_min = getRoundOffMinutes_time(overtime_min, time_increment);
				overtimehrs_final = parseFloat(overtime_split[0]) + parseFloat(overtime_min);
				overtimehrs_final= overtimehrs_final.toFixed(2);
			//code Ends
			
			$("#daily_rate_1_" + row_id).val(overtimehrs_final);
			overtimehrs_final = formatNumber(overtimehrs_final);
			ovt_total	=  parseFloat(ovt_total) +  parseFloat(overtimehrs_final);
		}
		
		// GETTING DOUBLETIME TOTAL HOURS
		doubletime_hours = $("#daily_rate_2_" + row_id).val();
		doubletime_hours = formatNumber(doubletime_hours);
		if (doubletime_hours != '' && doubletime_hours != '0.00' && doubletime_hours != 0)
		{
			//Rounding Code
				var double_timeh = doubletime_hours.split(".");
				if(double_timeh[1] =='' || double_timeh[1] == undefined)
				{
					double_timeh[1]=0;
				}
				var doubletime_min = '0.'+double_timeh[1];
				double_min = getRoundOffMinutes_time(doubletime_min, time_increment);
				doubletimehrs_final = parseFloat(double_timeh[0]) + parseFloat(double_min);
				doubletimehrs_final= doubletimehrs_final.toFixed(2);
			//Code Ends
			
			$("#daily_rate_2_" + row_id).val(doubletimehrs_final);
			doubletimehrs_final = formatNumber(doubletimehrs_final);
			dbt_total	= parseFloat(dbt_total) + parseFloat(doubletimehrs_final);
		}

		// GETTING DAY TOTAL HOURS
		day_total_hours	= parseFloat(reg_total) + parseFloat(ovt_total) + parseFloat(dbt_total);
		day_total_hours	= formatNumber(day_total_hours);
		//Rounding Code
			var day_tothrsnew = day_total_hours.split(".");
			if(day_tothrsnew[1] =='' || day_tothrsnew == undefined)
			{
				day_tothrsnew[1]=0;
			}
			var daytotalhrs_min = '0.'+day_tothrsnew[1];
			var daytotalhrs_mins = getRoundOffMinutes_time(daytotalhrs_min, time_increment);
			daytotalhrs_final = parseFloat(day_tothrsnew[0]) +parseFloat(daytotalhrs_mins); 
		//Code Ends
		$("#daytotalhrs_" + row_id).val(daytotalhrs_final);

		//$("#daytotalhrs_" + row_id).val(day_total_hours);
		//$("#singledaytotalhrs_" + row_id).val(parseFloat(regular_hours)+parseFloat(overtime_hours)+parseFloat(doubletime_hours));
	});

	// GETTING WEEK TOTAL HOURS
	total_hours	= parseFloat(total_hours) + parseFloat(day_total_hours);

	fmt_reg_hrs	= formatNumber(reg_total);
	fmt_ovt_hrs	= formatNumber(ovt_total);
	fmt_dbt_hrs	= formatNumber(dbt_total);
	fmt_tot_hrs	= formatNumber(total_hours);

	$("#final_regular_hours").html(fmt_reg_hrs);
	$("#final_overtime_hours").html(fmt_ovt_hrs);
	$("#final_doubletime_hours").html(fmt_dbt_hrs);
	$("#final_total_hours").html(fmt_tot_hrs);
}

// Revalidate fields for Seventh Day / Sixth Day Rule
function reCalculateTime(inout_rowid) {
	
	var rowid		= inout_rowid.split("_").pop(-1);

	var pre_inflag		= false;
	var pre_outflag		= false;
	var post_inflag		= false;
	var post_outflag	= false;

	var sel_date		= $("#daily_dates_" + rowid).val();
	var pre_intime		= $("#pre_intime_" + rowid).val();
	var pre_outtime		= $("#pre_outtime_" + rowid).val();
	var post_intime		= $("#post_intime_" + rowid).val();
	var post_outtime	= $("#post_outtime_" + rowid).val();

	var tot_pre_hours	= 0.00;
	var tot_post_hours	= 0.00;

	if (pre_outtime == "" || post_intime == "") {

		$("#break_hours_" + rowid).val("");
	}

	if ((pre_intime == "" && pre_outtime == "" && post_intime == "" && post_outtime == "") ||
	    (pre_intime != "" && pre_outtime == "") ||
	    (post_intime != "" && post_outtime == "") ||
	    (pre_intime == "" && pre_outtime != "") ||
	    (post_intime == "" && post_outtime != "")) {

		$("#daily_rate_0_" + rowid).val("");
		$("#daily_rate_1_" + rowid).val("");
		$("#daily_rate_2_" + rowid).val("");
		$("#singledaytotalhrs_" + rowid).val("");
		calculateregularhours(rowid);
	}

	if (pre_intime != "") {

		pre_inflag	= validateTime(pre_intime);

		if (!pre_inflag) {

			alert("Invalid Time-In. Please enter valid time.");

			$("#pre_intime_" + rowid).val("");

			setTimeout(function() {
				$("#pre_intime_" + rowid).focus();
			}, 0);

			return false;
		}
	}

	if (pre_outtime != "") {

		pre_outflag	= validateTime(pre_outtime);

		if (!pre_outflag) {

			alert("Invalid Time-Out. Please enter valid time.");

			$("#pre_outtime_" + rowid).val("");

			setTimeout(function() {
				$("#pre_outtime_" + rowid).focus();
			}, 0);

			return false;
		}
	}

	if (post_intime != "") {

		post_inflag	= validateTime(post_intime);

		if (!post_inflag) {

			alert("Invalid Time-In. Please enter valid time.");

			$("#break_hours_" + rowid).val("");
			$("#post_intime_" + rowid).val("");

			setTimeout(function() {
				$("#post_intime_" + rowid).focus();
			}, 0);

			return false;
		}
	}

	if (post_outtime != "") {

		post_outflag	= validateTime(post_outtime);

		if (!post_outflag) {

			alert("Invalid Time-Out. Please enter valid time.");

			$("#break_hours_" + rowid).val("");
			$("#post_outtime_" + rowid).val("");

			setTimeout(function() {
				$("#post_outtime_" + rowid).focus();
			}, 0);

			return false;
		}
	}

	if ((pre_inflag && pre_outflag) || (post_inflag && post_outflag)) {

		if (pre_inflag && pre_outflag) {

			if (pre_intime.substring(6).toUpperCase() == "PM" && pre_outtime.substring(6).toUpperCase() == "AM") {

				alert("Please enter Time-In & Time-Out for the same day");

				$("#pre_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#pre_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			var pre_in_str	= sel_date + " " + pre_intime;
			var pre_out_str	= sel_date + " " + pre_outtime;

			var pre_start_time	= Date.parse(pre_in_str);
			var pre_end_time	= Date.parse(pre_out_str);

			if (pre_end_time <= pre_start_time) {

				alert("Out-Time should be greater than In-Time");

				$("#pre_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#pre_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			// Rounds the time based on time increment
			if (time_increment !== "0") {
				pre_in_str	 = sel_date+" "+ pre_intime;	
				pre_out_str	 = sel_date+" "+ pre_outtime;

				pre_start_time	= Date.parse(pre_in_str);
				pre_end_time	= Date.parse(pre_out_str);
			}
			// Block ends here

			tot_pre_hours	= calculateTimeDifference(pre_start_time, pre_end_time);
		}

		if (pre_inflag && pre_outflag && post_inflag) {

			var str_pre_in	= sel_date + " " + pre_intime;
			var str_pre_out	= sel_date + " " + pre_outtime;
			var str_post_in	= sel_date + " " + post_intime;

			var pre_stime	= Date.parse(str_pre_in);
			var pre_etime	= Date.parse(str_pre_out);
			var post_stime	= Date.parse(str_post_in);

			if (post_stime <= pre_etime) {
					
				alert("Post Time-In should be greater than Pre Time-Out ");				

				$("#break_hours_" + rowid).val("");
				$("#post_intime_" + rowid).val("");

				setTimeout(function() {
					$("#post_intime_" + rowid).focus();
				}, 0);

				$("#post_intime_" + rowid).bind('focus', function() {
					$(this).select();
				});
				
				// Rounds the time based on time increment
				if (time_increment !== "0") {
					str_pre_in	 = sel_date+" "+pre_intime;	
					str_pre_out	 = sel_date+" "+pre_outtime;
					
					pre_stime	= Date.parse(str_pre_in);
					pre_etime	= Date.parse(str_pre_out);
				}
				// Block ends here

				tot_pre_hours	= calculateTimeDifference(pre_stime, pre_etime);

				total_hours	= parseFloat(tot_pre_hours);
				total_hours	= formatNumber(total_hours);

				$("#daily_rate_0_" + rowid).val(total_hours);
				$("#daytotalhrs_" + rowid).val(total_hours);
				$("#singledaytotalhrs_" + rowid).val(total_hours);
				
				calculateregularhours(rowid);

				return false;
			}

			if (post_stime >= pre_stime && post_stime <= pre_etime) {

				alert("Time-In hours are overlapped. Please re-enter the Time-In hours.");

				$("#post_intime_" + rowid).val("");
				$("#break_hours_" + rowid).val("");

				setTimeout(function() {
					$("#post_intime_" + rowid).focus();
				}, 0);

				// Rounds the time based on time increment
				if (time_increment !== "0") {
					str_pre_in	 = sel_date+" "+pre_intime;	
					str_pre_out	 = sel_date+" "+pre_outtime;

					pre_stime	= Date.parse(str_pre_in);
					pre_etime	= Date.parse(str_pre_out);
				}
				// Block ends here

				tot_pre_hours	= calculateTimeDifference(pre_stime, pre_etime);

				total_hours	= parseFloat(tot_pre_hours);
				total_hours	= formatNumber(total_hours);

				$("#daily_rate_0_" + rowid).val(total_hours);
				$("#daytotalhrs_" + rowid).val(total_hours);
				$("#singledaytotalhrs_" + rowid).val(total_hours);

				calculateregularhours(rowid);
				return false;
			}

			var break_hours	= calculateBreakHours(pre_etime, post_stime);

			$("#break_hours_" + rowid).val(break_hours);
		}

		if (post_inflag && post_outflag) {

			if (post_intime.substring(6).toUpperCase() == "PM" && post_outtime.substring(6).toUpperCase() == "AM") {

				alert("Please enter Time-In & Time-Out for the same day");

				$("#post_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#post_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			var post_in_str		= sel_date + " " + post_intime;
			var post_out_str	= sel_date + " " + post_outtime;

			var post_start_time	= Date.parse(post_in_str);
			var post_end_time	= Date.parse(post_out_str);

			if (post_end_time <= post_start_time) {

				alert("Out-Time should be greater than In-Time");

				$("#post_outtime_" + rowid).val("");

				setTimeout(function() {
					$("#post_outtime_" + rowid).focus();
				}, 0);

				return false;
			}

			// Rounds the time based on time increment		
			if (time_increment !== "0") {
				post_in_str	 = sel_date+" "+post_intime;	
				post_out_str	 = sel_date+" "+post_outtime;

				post_start_time	= Date.parse(post_in_str);
				post_end_time	= Date.parse(post_out_str);
			}
			// Block ends here

			tot_post_hours	= calculateTimeDifference(post_start_time, post_end_time);
		}

		total_hours	= parseFloat(tot_pre_hours) + parseFloat(tot_post_hours);
		total_hours	= formatNumber(total_hours);

		$("#daily_rate_0_" + rowid).val(total_hours);
		$("#daytotalhrs_" + rowid).val(total_hours);
		$("#singledaytotalhrs_" + rowid).val(total_hours);

		calculateregularhours(rowid);

	} else {

		$("#daily_rate_0_" + rowid).val("");
		$("#daily_rate_1_" + rowid).val("");
		$("#daily_rate_2_" + rowid).val("");
		$("#singledaytotalhrs_" + rowid).val("");
	}
}

// find if element exists in array
function findInArray(search_arr, search_val, index){
	
	var key, found;
	
	found = false;
	
	for (var i = 0; i < search_arr.length; i++) {	
		if (search_arr[i][index] == search_val) { 
		    found = true;
		    break;
		}		
	}
	
    return found;
}

// Sorts the dates in dyna_dates array
function SortByDate(a, b){
  var aDate = parseDate(a[1]);
  var bDate = parseDate(b[1]);
  return ((aDate < bDate) ? -1 : ((aDate > bDate) ? 1 : 0));
}


// parse a date in mm/dd/yyyy format
function parseDate(input) {	
  var parts = input.split('/');  
  return new Date(parts[2], parts[0]-1, parts[1]); // Note: months are 0-based
}

function calculateregularhours(current_rowid) {


	var reg_total	= 0.00;
	var ovt_total	= 0.00;
	var dbt_total	= 0.00;

	var total_hours		= 0.00;
	var regular_hours	= 0.00;
	var overtime_hours	= 0.00;
	var doubletime_hours	= 0.00;
	var day_total_hours	= 0.00;
	var dbt_hours = 0.00;

	var row_id;
	var inputid;

	var fmt_reg_hrs;
	var fmt_ovt_hrs;
	var fmt_dbt_hrs;
	var fmt_tot_hrs;
	var daytotalhrs_final;
	var doubletimehrs_final;
	
	$(".rowRegularHours").each(function() {

		inputid	= $(this).attr("id");
		row_id	= inputid.split("_").pop(-1);

		regular_hours	= $("#daily_rate_0_" + row_id).val();

		if (regular_hours != "") {
			//Rounding Code
			regular_hours= formatNumber(regular_hours);
			//var regular_hours = regular_hours.toFixed(2);
			var regular_hours_time = regular_hours.split(".");
			if(regular_hours_time[1] =='' || regular_hours_time[1] == undefined){
				regular_hours_time[1]=0;
			}
			var regular_hours_mint = '0.'+regular_hours_time[1];
			regular_hours_min = getRoundOffMinutes_time(regular_hours_mint, time_increment);
			regular_hours = parseFloat(regular_hours_time[0]) + parseFloat(regular_hours_min);
			//Code Ends
			if (parseFloat(regular_hours) > parseFloat(max_reg_hrs)) {

				// GETTING REGULAR TOTAL HOURS
				reg_total	= parseFloat(reg_total) + parseFloat(max_reg_hrs);
						
				$("#daily_rate_0_" + row_id).val(formatNumber(max_reg_hrs));

				cal_over_time	= parseFloat(regular_hours) - parseFloat(max_reg_hrs);
				
				if (parseFloat(cal_over_time) > parseFloat(max_ovt_hrs)) {
					// GETTING DOUBLE TIME TOTAL HOURS
					var dbt_hours	= parseFloat(cal_over_time) - parseFloat(max_ovt_hrs);
					//Rounding Code
					var dbt_hours = dbt_hours.toFixed(2);
					var dbt_time = dbt_hours.split(".");
					if(dbt_time[1] =='' || dbt_time[1] == undefined){
						dbt_time[1]=0;
					}
					var dbt_hr_mint = '0.'+dbt_time[1];
					dbt_min = getRoundOffMinutes_time(dbt_hr_mint, time_increment);
					doublehrs_final = parseFloat(dbt_time[0]) + parseFloat(dbt_min);
					//Code Ends
					$("#daily_rate_1_" + row_id).val(max_ovt_hrs);

					
					$("#daily_rate_2_" + row_id).val(doublehrs_final);


				} else {
					var sel_date		= $("#daily_dates_" + row_id).val();
					//Rounding Code
					cal_over_time= cal_over_time.toFixed(2);
					var ovr_time = cal_over_time.split(".");
					if(ovr_time[1] =='' || ovr_time[1] == undefined){
						ovr_time[1]=0;
					}
					var ovr_hr_mint = '0.'+ovr_time[1];
					ovr_min = getRoundOffMinutes_time(ovr_hr_mint, time_increment);
					overhrs_final = parseFloat(ovr_time[0]) + parseFloat(ovr_min);
					overhrs_final= overhrs_final.toFixed(2);
					//Code Ends
					if (cal_over_time > 0) {
						$("#daily_rate_1_" + row_id).val(overhrs_final);

					} else {
						$("#daily_rate_1_" + row_id).val("");

					}
					$("#daily_rate_2_" + row_id).val("");

				}

			} else {

				// GETTING REGULAR TOTAL HOURS
				var sel_date		= $("#daily_dates_" + row_id).val();

				//Rounding Code
				var regular_hours = regular_hours.toFixed(2);
				var reg_time = regular_hours.split(".");
				if(reg_time[1] =='' || reg_time[1] == undefined){
					reg_time[1]=0;
				}
				var hr_mint = '0.'+reg_time[1];
				reg_min = getRoundOffMinutes_time(hr_mint, time_increment);
				regularhrs_final = parseFloat(reg_time[0]) + parseFloat(reg_min);
				fmt_reg_hrs	= formatNumber(regularhrs_final);
				regular_hours = regularhrs_final.toFixed(2);
				
				//Code Ends
				reg_total	= parseFloat(reg_total) + parseFloat(regular_hours);
				$("#daily_rate_0_" + row_id).val(regular_hours);
				
				if (regular_hours <= formatNumber(max_reg_hrs) && $("#daily_rate_1_" + row_id).val() == "") {
					$("#daily_rate_1_" + row_id).val("");
					$("#daily_rate_2_" + row_id).val("");
				}
				else if (row_id == current_rowid && regular_hours <= formatNumber(max_reg_hrs)) {
					$("#daily_rate_1_" + row_id).val("");
					$("#daily_rate_2_" + row_id).val("");
				}
			}

			// GETTING OVERTIME TOTAL HOURS
			overtime_hours		= $("#daily_rate_1_" + row_id).val();

			// To handle NaN and empty values
			if (overtime_hours != '' && overtime_hours != '0.00' && overtime_hours != 0) {
				// Rounding Code
				var overtime_split = overtime_hours.split(".");
				if(overtime_split[1] == '' || overtime_split[1] == undefined){
					overtime_split[1]=0;
				}	
				var overtime_min = '0.'+overtime_split[1];
				overtime_min = getRoundOffMinutes_time(overtime_min, time_increment);
				overtimehrs_final = parseFloat(overtime_split[0]) + parseFloat(overtime_min);
				overtimehrs_final= overtimehrs_final.toFixed(2);
				//code Ends
				
				$("#daily_rate_1_" + row_id).val(overtimehrs_final);

				ovt_total	=  parseFloat(ovt_total) +  parseFloat(overtimehrs_final);
			}
		
			// GETTING DOUBLETIME TOTAL HOURS
			doubletime_hours	= $("#daily_rate_2_" + row_id).val();
			
			doubletime_hours	= formatNumber(doubletime_hours);
			// To handle NaN and empty values
			if (doubletime_hours != '' && doubletime_hours != '0.00' && doubletime_hours != 0) {
				//Rounding Code
				var double_timeh = doubletime_hours.split(".");
				if(double_timeh[1] =='' || double_timeh[1] == undefined){
					double_timeh[1]=0;
				}
				
				var doubletime_min = '0.'+double_timeh[1];
				double_min = getRoundOffMinutes_time(doubletime_min, time_increment);
				doubletimehrs_final = parseFloat(double_timeh[0]) + parseFloat(double_min);
				doubletimehrs_final= doubletimehrs_final.toFixed(2);
				//Code Ends
				$("#daily_rate_2_" + row_id).val(doubletimehrs_final);

				doubletimehrs_final = formatNumber(doubletimehrs_final);
				dbt_total	= parseFloat(dbt_total) + parseFloat(doubletimehrs_final);
			}
			
		
			// GETTING DAY TOTAL HOURS
			day_total_hours	= parseFloat(reg_total) + parseFloat(ovt_total) + parseFloat(dbt_total);
			//Rounding Code
			day_total_hours= day_total_hours.toFixed(2);
			var day_tothrsnew = day_total_hours.split(".");
			if(day_tothrsnew[1] =='' || day_tothrsnew == undefined){
				day_tothrsnew[1]=0;
			}
			var daytotalhrs_min = '0.'+day_tothrsnew[1];
			var daytotalhrs_mins= getRoundOffMinutes_time(daytotalhrs_min, time_increment);
			daytotalhrs_final = parseFloat(day_tothrsnew[0]) +parseFloat(daytotalhrs_mins); 
			//Code Ends
			$("#daytotalhrs_" + row_id).val(daytotalhrs_final);
		}
	});

	// GETTING WEEK TOTAL HOURS
	total_hours	= parseFloat(total_hours) + parseFloat(day_total_hours);
	fmt_reg_hrs	= formatNumber(reg_total);
	fmt_ovt_hrs	= formatNumber(ovt_total);
	fmt_dbt_hrs	= formatNumber(dbt_total);
	fmt_tot_hrs	= formatNumber(total_hours);

	$("#final_regular_hours").html(fmt_reg_hrs);
	$("#final_overtime_hours").html(fmt_ovt_hrs);
	$("#final_doubletime_hours").html(fmt_dbt_hrs);
	$("#final_total_hours").html(fmt_tot_hrs);
}

function AddTaskDetails(rownumberval) {

	var rowval	= rownumberval.split("_");
	var rowid	= rowval[1];

	$("#taskTB_"+rowid).show();
	$("#taskTB_"+rowid).focus();
	$("#textlabel_"+rowid).hide();
}

function getBlured(dynid){

	var rowval	= dynid.split("_");
	var rowid	= rowval[1];

	var td_value	= $.trim($("#taskTB_"+rowid).val());

	if (td_value.length > 0) {

		$("#textlabel_"+rowid).text(td_value);
		$("#textlabel_"+rowid).css("display", "block");
	}

	$("#taskTB_"+rowid).hide();
}

function chainNavigation(rowid) {

    if (rowid === undefined) {
		$('.rowIntime').inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 }, {"oncomplete": function(){ calculateTime(this.id); }});
		$('.rowOuttime').inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 }, {"oncomplete": function(){ calculateTime(this.id); }});
	}else
	{
		$("#row_"+rowid+" .rowIntime").inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 }, {"oncomplete": function(){ calculateTime(this.id); }});
		$("#row_"+rowid+" .rowOuttime").inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 }, {"oncomplete": function(){ calculateTime(this.id); }});

	}
	tabindexcount	= $("#tabindexcount").val();

	$("#issues").attr("tabindex", (parseInt(tabindexcount)+1));
	$("#timefile").attr("tabindex", (parseInt(tabindexcount)+2));
	$("#tabindexcount").val(parseInt(tabindexcount));



	var selects	= document.getElementsByTagName("select");

	for (var i = 0; i < selects.length; i++) {

		var sl	= selects[i];
		while (sl = sl.parentNode) {
			if(sl.nodeName.toLowerCase() === 'tr') {
				selects[i].onfocus = function() {
					this.parentRow.style.backgroundColor = '#3fb8f1';
				};
				selects[i].onblur = function() {
					this.parentRow.style.backgroundColor = '';
				};
				selects[i].parentRow = sl;
				break;
			}
		}
	}
}

function closeWindow() {

	window.close();
}

function editTimeSheet(module, status, sno) {

	var form	= document.summary;
	var clivalstr	= "";
	if (module == "MyProfile") {
		form	= document.sheet;
	}
	else if (module == "Client") {
		var clival 	= form.clival.value;
		clivalstr	= "&clival="+clival;
	}
        //Timesheet grid load optimization --add param in url
        if(window.opener.location.href.indexOf('/BSOS/Accounting/Time_Mngmt/histimesheets.php') > 0){
            form.action	= "/include/timeintimeout.php?mode=edit&module=" + module + "&ts_status=" + status + "&sno=" + sno+clivalstr+"&timeopttype=Approved";
        }else if(window.opener.location.href.indexOf('/BSOS/Accounting/Time_Mngmt/empfaxhis.php') > 0){
            form.action	= "/include/timeintimeout.php?mode=edit&module=" + module + "&ts_status=" + status + "&sno=" + sno+clivalstr+"&timeopttype=Submitted";   
        }else if(window.opener.location.href.indexOf('/BSOS/Accounting/Time_Mngmt/rejectedtimesheets.php') > 0){
            form.action	= "/include/timeintimeout.php?mode=edit&module=" + module + "&ts_status=" + status + "&sno=" + sno+clivalstr+"&timeopttype=Rejected";
        }else if(window.opener.location.href.indexOf('/BSOS/Accounting/Time_Mngmt/exportedtimesheets.php') > 0){
            form.action	= "/include/timeintimeout.php?mode=edit&module=" + module + "&ts_status=" + status + "&sno=" + sno+clivalstr+"&timeopttype=Exported";
        }else{
            form.action	= "/include/timeintimeout.php?mode=edit&module=" + module + "&ts_status=" + status + "&sno=" + sno+clivalstr;  
        }
	form.submit();
}

function printTimesheet(module, status, parid, mode) {

	var v_width	= window.screen.availWidth - 10;
	var v_height	= window.screen.availHeight - 50;

	var win_name	= "TimeInTimeOut";
	var win_url	= "/include/print_timeintimeout.php?module="+module+"&parid=" + parid + "&tmstatus=" + status + "&mode="+mode;
	var win_param	= "width=" + v_width + "px,height=" + v_height + "px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes";

	var remote	= window.open(win_url, win_name, win_param);

	remote.focus();
}

function printPDFTimesheet(module, status_id, id,type) {

	var v_heigth 	= window.screen.availHeight-50;
	var v_width 	= window.screen.availWidth-10;
	var mode 	= '';
	var Type	=(type=='sub')?"&prtype="+type:"";
	var module_tmstatus = "&module="+module+"&tmstatus="+status_id;
	var addr 	= "&addr="+id;
	var clivalstr 	= "";
	if (module == "Accounting") {
		addr = "&addr1="+id;
	}
	else if (module == "Client") {
		var form	= document.summary;
		var clival 	= form.clival.value;
		clivalstr	= "&clival="+clival;
	}
	
	if(window.opener.document.getElementById("mode") !== null){
	    var modevalue = window.opener.document.getElementById("mode").value;
	    var mode = "&mode="+modevalue;
	}
	
	path = "/include/tcpdf/print/print_timesheets.php?from=popup"+addr+Type+module_tmstatus+mode+clivalstr;
	remote=window.open(path,"timesheets","width="+v_width+"px,height="+v_heigth+"px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes");
	remote.focus(); 
}

function printPDFInvoiceTimesheet(module, status_id, id,type, invoice, asgmnt_id) {

	var v_heigth = window.screen.availHeight-50;
	var v_width = window.screen.availWidth-10;

	var Type=(type=='sub')?"&prtype="+type:"";
	var module_tmstatus = "&module="+module+"&tmstatus="+status_id+"&invoice="+invoice;
	var addr = "&addr="+id;
	if (module == "Accounting") {
		addr = "&addr1="+id;
	}

	path	= "/include/tcpdf/print/print_timesheets.php?from=popup"+addr+Type+module_tmstatus;

	if (asgmnt_id != "") {

		path += "&rowAsgn=" + asgmnt_id;
	}

	remote=window.open(path,"timesheets","width="+v_width+"px,height="+v_heigth+"px,statusbar=yes,menubar=no,scrollbars=yes,left=0,top=0,dependent=yes,resizable=yes");
	remote.focus(); 
}

function viewNotes(tmstatus, addr1, module,val) {

	var v_height	= 400;
	var v_width		= 1200;

	var left1	= (window.screen.availWidth-v_width) / 2;
	var top1	= (window.screen.availHeight-v_height) / 2;

	var win_name	= "TimeInTimeOutNotes";
	var win_url	= "/include/timeintimeout_notes.php?tmstatus="+tmstatus+"&addr1="+addr1+"&module="+module+"&date=" + val;
	var win_param	= "width=" + v_width + "px, height=" + v_height + "px, statusbar=no, menubar=no, scrollbars=yes, left=" + left1 + "px, top=" + top1 + "px, dependent=yes, resizable=no";

	var remote	= window.open(win_url, win_name, win_param);

	remote.focus();
}

function delTimeAttach(sno, parid) {

	var url	= "delete_timefile.php?sno="+sno+"&parid="+parid;

	$.get(url, function( data ) {
		$("#"+sno).remove();
	});
}

function formatNumber(val) {

	var i		= parseFloat(val);
	var minus	= "";

	if (isNaN(i)) {

		i	= 0.00;
	}

	if (i < 0) {

		minus = "-";
	}

	i	= Math.abs(i);
	i	= parseInt((i + .005) * 100);
	i	= i / 100;

	var s	= new String(i);

	if (s.indexOf(".") < 0) {

		s += ".00";
	}

	if (s.indexOf(".") == (s.length - 2)) {

		s += "0";
	}

	s	= minus + s;

	return s;
}

function calculateTimeDifference(start_time, end_time) {

	var t_hours	= 0.00;
	var diff	= (end_time - start_time)/1000/60;
	var hours	= formatNumber(String(100 + Math.floor(diff / 60)).substr(1));
	var mins	= String(100 + diff % 60).substr(1);
	var tdate	= new Date(end_time);

	mins	= formatNumber(mins/60); // Required as converting from hours:mins to decimal [1min = 1/60 or 30mins = 0.5]
	
	// Checking entered time is 11:59 PM, then adding one min to consider as 12:00 AM.
	if(tdate.getHours() == "23" && tdate.getMinutes() == "59")
	{
		mins	= parseFloat(mins) + 0.02;
	}

	if (hours != "aN" && mins != "aN") {

		t_hours	= parseFloat(hours) + parseFloat(mins);
		t_hours	= formatNumber(t_hours);
	}

	return t_hours;
}

function calculateBreakHours(start_time, end_time) {

	var b_hours	= 0.00;
	var diff	= (end_time - start_time)/1000/60;
	var hours	= String(100 + Math.floor(diff / 60)).substr(1);
	var mins	= String(100 + diff % 60).substr(1);

	if (hours != "aN" && mins != "aN") {

		b_hours	= hours + ":" + mins;
	}

	return b_hours;
}

function validateTimeInOut(act, mode) {
	
	// SIXTH AND SEVENTH DAY RULE
	var rule_msg = "";
	
	var ele = document.getElementById("timesubmit");
	elehref = ele.href;
	ele.href = 'javascript:void(0)';
	var flag	= true;
	var val		= $("#MainTable input.chremove[type=checkbox]").length;

	if (val == 0) {

		alert("Please add atleast one row to submit timesheet");
		ele.href = elehref;
		flag	= false;
		return;
	}

	form	= document.sheet;
	form.rowcou.value	= parseInt(val);

	var tot_hours	= 0;
	var billable	= "No";
	var getdates	= "";
	var assignment	= "";
	var start_date	= "";

	var sdate	= "";
	var smon	= "";
	var sday	= "";
	var syer	= "";
	var data	= "";

	var pre_intime		= "";
	var pre_outtime		= "";
	var breakhours		= "";
	var post_intime		= "";
	var post_outtime	= "";
	var daily_classes	= "";
	var getdates_valid	= "";
	var rg_ovt_dbt_hours = 0;
	var dup_flag	= checkDuplicateTimesheets();

	if (dup_flag) {

		///////////////////////// File size validation ///////////////////////////////

		var max_upload	= document.getElementById("max_upload").value;
		var allowedSize	= parseInt(max_upload)/(1024 * 1024);

		try {

			var uploadedFile	= document.getElementById("timefile");
			var fileSize		= parseInt(uploadedFile.files[0].size)/(1024 * 1024);

			if (fileSize > allowedSize) {

				alert("You can attach files of max.size " + allowedSize + " MB");
				ele.href = elehref;
				flag	= false;
				return;
			}

		} catch(e) {}

		var checking_from	= form.checking_from.value;
		var checking_to		= form.checking_to.value;

		form.servicedate.value		= checking_from;
		form.servicedateto.value	= checking_to;

		var dynamicrowcount	= document.getElementById("dynrowcount").value;
		var dynamiccolcount	= document.getElementById("colcount").value;

		for (dynamicrowindex = 0; dynamicrowindex <= dynamicrowcount; dynamicrowindex++) {

			for (dynamiccolindex = 0; dynamiccolindex < dynamiccolcount;dynamiccolindex++) {

				if (checkObject(document.getElementById("daily_rate_"+dynamiccolindex+"_"+dynamicrowindex))) {

					if (document.getElementById("daily_rate_"+dynamiccolindex+"_"+dynamicrowindex).value) {

						selected_date	= document.getElementById("daily_dates_"+dynamicrowindex)[document.getElementById("daily_dates_"+dynamicrowindex).selectedIndex].value;

						if (getdates_valid == "") {

							getdates_valid	= selected_date;

						} else {

							if (getdates_valid.indexOf(selected_date) < 0) {

								getdates_valid	+= "^"+selected_date;
							}
						}
					}
				}
			}
		}

		var taskelt, tothrs, ovthrs, dbthrs, sumhrs;
		var maxhrs	= parseFloat(wk_maxlimithoursaday);

		$(".rowRegularHours").each(function() {

			var inputid	= $(this).attr("id");
			var i	= inputid.split("_").pop(-1);

			taskelt	= document.getElementById('taskTB_'+i);
			tothrs	= document.getElementById('daily_rate_0_'+i).value;

			if (tothrs > 24) {

				alert("You can not fill more than 24 hours in a day.");
				ele.href = elehref;
				flag	= false;
				return;
			}
			
			if (ruletype_flag == "weekrule" && wk_maxlimithourspref == "N") {
				sumhrs	= 0.0;
				ovthrs	= document.getElementById('daily_rate_1_'+i).value;
				dbthrs	= document.getElementById('daily_rate_2_'+i).value;
				
				if (tothrs != "") {

					sumhrs	= sumhrs + parseFloat(tothrs);
				}

				if (ovthrs != "") {

					sumhrs	= sumhrs + parseFloat(ovthrs);
				}

				if (dbthrs != "") {

					sumhrs	= sumhrs + parseFloat(dbthrs);
				}

				rg_ovt_dbt_hours	=  parseFloat(rg_ovt_dbt_hours) + parseFloat(sumhrs);
				
			}

			if (ruletype_flag == "weekrule" && wk_maxlimithourspref == "Y") {

				sumhrs	= 0.0;
				ovthrs	= document.getElementById('daily_rate_1_'+i).value;
				dbthrs	= document.getElementById('daily_rate_2_'+i).value;

				if (tothrs != "") {

					sumhrs	= sumhrs + parseFloat(tothrs);
				}

				if (ovthrs != "") {

					sumhrs	= sumhrs + parseFloat(ovthrs);
				}

				if (dbthrs != "") {

					sumhrs	= sumhrs + parseFloat(dbthrs);
				}

				sumhrs	= parseFloat(sumhrs);
				rg_ovt_dbt_hours	=  parseFloat(rg_ovt_dbt_hours) + parseFloat(sumhrs);
				
				if (wk_ruleflag == "Y") {

					if ((parseFloat(tothrs) > maxhrs || parseFloat(ovthrs) > maxhrs || parseFloat(dbthrs) > maxhrs) || sumhrs > maxhrs) {

						alert("You are only allowed to submit "+wk_maxlimithoursaday+" hours a day.");
						ele.href	= elehref;
						flag		= false;
						return false;
					}

					chkdupdate_exceedmaxhours	= checkDuplicateDates();

					if (chkdupdate_exceedmaxhours) {

						alert("You are only allowed to submit "+wk_maxlimithoursaday+" hours a day.");
						ele.href	= elehref;
						flag		= false;
						return false;
					}
				}
			}

			if (checkForSpecialChars(taskelt)) {

				alert("The Task Details field do not accept ^ | characters. Please re-enter Task details.");
				ele.href = elehref;
				flag	= false;
				return;
			}

			tot_hours	+= document.getElementById("daily_rate_0_" + i).value;
		});

		form.aa.value		= act;
		form.timedata.value	= data;
		form.getdates.value	= getdates;
		form.rowcou.value	= parseInt(val);

		if (flag) {

			if (act == 'save' && ruletype_flag == "weekrule" &&  rg_ovt_dbt_hours <= 0  ) {

				alert("You can not save a timesheet without hours.");
				ele.href = elehref;
				flag	= false;
				return;

			}
			else if(act == 'submit' && ruletype_flag == "weekrule" &&  rg_ovt_dbt_hours <= 0 ) {
					
				alert("You can not submit a timesheet without hours.");
				ele.href = elehref;
				flag	= false;
				return;
					
			}else if(ruletype_flag != "weekrule" && tot_hours == 0 || tot_hours == "0.00") {
				if (act == 'save') {

					alert("You can not save a timesheet without hours.");

				} else {

					alert("You can not submit a timesheet without hours.");
				}
				ele.href = elehref;
				flag	= false;
				return;
			}
			else {
				
				rule_msg = checkRules();
				
				// SEVENTH DAY RULE SPECIFIC				
				if (rule_msg != "") {
					alert(rule_msg);
				}						
				// END SEVENTH DAY RULE	

				/* Variables used for Disclaimer */
				var empUsernames = form.empnames.value;
				var moduleName = form.module.value;
				
				dis_empUsernames	= empUsernames;
				dis_moduleName		= moduleName;
				dis_checking_to		= checking_to;
				dis_checking_from	= checking_from;
				dis_getdates		= getdates_valid;//getdates;

				/* Show Disclaimer - For ESS User and Disclaimer Content Exists and On Timesheet Submission */
				if (form.module.value == 'MyProfile' && checkObject(document.getElementById('disclaimer_content')) && document.getElementById('disclaimer_content').innerHTML != "" && act == "submit") {

					PopMsgHeadArr['timesheet_disclaimer']='Disclaimer';
					PopMsgFLineArr['timesheet_disclaimer']="";
					PopMsgQueArr['timesheet_disclaimer']="";
					PopMsgSLineArr['timesheet_disclaimer']="";
					PopMsgBtnTxtArr['submit']='Submit';
					PopMsgBtnValArr['submit']='submit';
					PopMsgExtMsgArr['timesheet_disclaimer']="<table border=\"0\" cellpadding=\"2\" cellspacing=\"1\" width=\"98%\"><tr><td><div id='disclaimer_container' style='overflow-y: scroll;height: 250px'>"+document.getElementById('disclaimer_content').innerHTML+"</div></td></tr></table>";
					display_Dynmic_Message('timesheet_disclaimer','cancel','submit','','displayDisclaimerAlert');

				} else {

					var empUsernames	= form.empnames.value;
					var moduleName		= form.module.value;
					var parid		= form.addr1.value;
					var lockdownflag 	= form.lockdown_flag.value;
					var is_ess_user		= form.ess_user.value;
					var ts_snos_str 	= "";
					var ts_status		= form.ts_status.value;
					$(".chremove").each(function(){
						if (ts_snos_str=="") {
							ts_snos_str = $(this).val();
						}
						else{
							ts_snos_str += ","+$(this).val();
						}
					});
					var url	= "/BSOS/Include/getAsgn.php";

					if (mode == 'edit') {
						if (is_ess_user == "YES" && lockdownflag == "dont_allow_duplicate")
						{
							var submitDatesValFlag = 'YES';						
							var ts_par_id 	= form.addr1.value;
							var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id+"&ts_status="+ts_status;
							var url = "/BSOS/Include/getAsgn.php";
							DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusCallBack('"+submitDatesValFlag+"','"+getdates_valid+"','NO','"+moduleName+"')");
							return;							
						}
						else
						{
							if (form.timeSubmitFlag.value == 1) {
	
								var submitDatesValFlag = 'YES';																
								//Handling the multiple user with same action
								var ts_par_id 	= form.addr1.value;
								var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id+"&ts_status="+ts_status;
								var url = "/BSOS/Include/getAsgn.php";
																								DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusCallBack('"+submitDatesValFlag+"','"+getdates_valid+"','NO','"+moduleName+"')");
								return;	
	
							} else {
	
								var submitDatesValFlag = 'NO';
								//Handling the multiple user with same action
								var ts_par_id 	= form.addr1.value;
								var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id+"&ts_status="+ts_status;
								var url = "/BSOS/Include/getAsgn.php";
								DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusCallBack('"+submitDatesValFlag+"','','','"+moduleName+"')");
								return;								
							}
						}

					} else {

						timesheetSubmitCheckAlert(form);
						var content	= "rtype=getTimesheetStatus&multiple=NO&dateRangeFilled=NO&moduleName="+moduleName+"&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates_valid+"&lockdown_flag="+lockdownflag;

						DynCls_Ajax_result(url, 'rtype', content, "getValidateTimesheet('single')");
						return;
					}
				}
			}
		}
	}
}
/////////////  END of TIME IN OUT Validations ///////////////////////

function AjaxTimesheetLockingStatusCallBack(DateValFlag, getdates_valid, dateRangeFilled, moduleName)
{
	var AjaxRes=DynCls_Ajx_responseTxt.split("|");
	form	= document.sheet;
	if(AjaxRes[0]==1)
	{
		var is_ess_user		= form.ess_user.value;
		if (moduleName=="MyProfile") {						
			alert('Timesheet has been modified by '+AjaxRes[2]+'.Please click on OK to close the timesheet.');
			window.opener.location.reload(true);
		}
		else{			
			alert('Timesheet has already been '+AjaxRes[1]+' by '+AjaxRes[2]+'.Please find this in '+AjaxRes[1]+' Timesheets.');
			window.opener.doGridSearch('search');
		}
		
		window.close();
		return;
	}
	else{
		//Call this for already submitted validation for date range..		
		timesheetSubmitCheckAlert(form);
		if (DateValFlag=="YES") {
						
			var empUsernames 	= form.empnames.value;
			var parid 		= form.addr1.value
			var lockdownflag 	= form.lockdown_flag.value;
			var checking_from 	= form.checking_from.value;
			var checking_to 	= form.checking_to.value;
			
			var content 		= "rtype=getTimesheetStatus&multiple=NO&moduleName="+moduleName+"&dateRangeFilled="+dateRangeFilled+"&timeEdit=YES&parid="+parid+"&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates_valid+"&lockdown_flag="+lockdownflag;
			var url 		= "/BSOS/Include/getAsgn.php";

			DynCls_Ajax_result(url,'rtype',content,"getValidateTimesheet('single')");
			return;	
		}
		else{
			singleTimesheetSubmit();
		}
	}
}

// check sixth and seventh day rule - invoked before submit
function checkRules(){
	
	var check_rule_msg = "";
	var check_dyna_list  = "";
	
	if (dyna_dates.length >= days_to_check || dyna_dates.length >= 6){		
		// Order the elements in the set
		dyna_dates.sort(SortByDate);
				
		// Create a string of all unique six/seven days
		for (var i = 0; i < dyna_dates.length; i++) {						
			check_dyna_list += dyna_dates[i][1]+"#";
		}
		
		// removes # from the end of the string
		check_dyna_list = check_dyna_list.slice(0, - 1);
		
		//Check the state of selected assignments before showing the alert.
		rule_condition3 = chk_state_of_asgn();
		
		if (check_dyna_list.indexOf(service_list) > -1 && dyna_dates.length > 6 && rule_condition2 == true && pos_weekstartday > -1 && rule_condition3 == true){
			check_rule_msg = "Hours entered on 6th Day ("+sixth_day_name+") and 7th Day ("+seventhdayrule_weekendday+") will be considered under Overtime and Doubletime.";
		}/*else if(found_nonconsecutive_days == true) {
			check_rule_msg = "Overtime Rules Applicable";
		}*/else{
			check_rule_msg = "";
		}
	}
	return check_rule_msg;
}

function checkDuplicateTimesheets() {

	var row_count	= 0;
	var date_rowid	= [];
	var cflag	= true;
	var msg		= "";

	$(".rowRegularHours").each(function() {

		var inputid	= $(this).attr("id");
		var rowid	= inputid.split("_").pop(-1);

		var sel_date		= $("#daily_dates_" + rowid).val();
		var pre_intime		= $("#pre_intime_" + rowid).val();
		var pre_outtime		= $("#pre_outtime_" + rowid).val();
		var post_intime		= $("#post_intime_" + rowid).val();
		var post_outtime	= $("#post_outtime_" + rowid).val();

		if (pre_intime != "" && pre_outtime == "") {

			alert("Please enter Time-Out");

			setTimeout(function() {
				$("#pre_outtime_" + rowid).focus();
			}, 0);

			cflag	= false;
			return false;

		} else if (pre_intime == "" && pre_outtime != "") {

			alert("Please enter Time-In");

			setTimeout(function() {
				$("#pre_intime_" + rowid).focus();
			}, 0);

			cflag	= false;
			return false;

		} else if (post_intime != "" && post_outtime == "") {

			alert("Please enter Time-Out");

			setTimeout(function() {
				$("#post_outtime_" + rowid).focus();
			}, 0);

			cflag	= false;
			return false;

		} else if (post_intime == "" && post_outtime != "") {

			alert("Please enter Time-In");

			setTimeout(function() {
				$("#post_intime_" + rowid).focus();
			}, 0);

			cflag	= false;
			return false; 
		}

		if ((pre_intime != "" && pre_outtime != "") || (post_intime != "" && post_outtime != "")) {

			if (pre_intime != "" && pre_outtime != "") {

				if (pre_intime.substring(6).toUpperCase() == "PM" && pre_outtime.substring(6).toUpperCase() == "AM") {

					alert("Please enter Time-In & Time-Out for the same day");

					$("#pre_outtime_" + rowid).val("");

					setTimeout(function() {
						$("#pre_outtime_" + rowid).focus();
					}, 0);

					cflag	= false;
					return false;
				}

				var pre_start_time	= getUnixTimeStamp(sel_date + " " + pre_intime);
				var pre_end_time	= getUnixTimeStamp(sel_date + " " + pre_outtime);

				if (pre_end_time <= pre_start_time) {

					alert("Out-Time should be greater than In-Time");

					$("#pre_outtime_" + rowid).val("");

					setTimeout(function() {
						$("#pre_outtime_" + rowid).focus();
					}, 0);

					cflag	= false;
					return false;
				}
			}

			if (pre_intime != "" && pre_outtime != "" && post_intime != "") {

				var pre_stime	= getUnixTimeStamp(sel_date + " " + pre_intime);
				var pre_etime	= getUnixTimeStamp(sel_date + " " + pre_outtime);
				var post_stime	= getUnixTimeStamp(sel_date + " " + post_intime);

				if (post_stime <= pre_etime) {

					alert("Post Time-In should be greater than Pre Time-Out ");

					$("#post_intime_" + rowid).val("");

					setTimeout(function() {
						$("#post_intime_" + rowid).focus();
					}, 0);

					cflag	= false;
					return false;
				}

				if (post_stime >= pre_stime && post_stime <= pre_etime) {

					alert("Time-In hours are overlapped. Please re-enter the Time-In hours.");

					$("#post_intime_" + rowid).val("");
					$("#break_hours_" + rowid).val("");

					setTimeout(function() {
						$("#post_intime_" + rowid).focus();
					}, 0);

					cflag	= false;
					return false;
				}
			}

			if (post_intime != "" && post_outtime != "") {

				if (post_intime.substring(6).toUpperCase() == "PM" && post_outtime.substring(6).toUpperCase() == "AM") {

					alert("Please enter Time-In & Time-Out for the same day");

					$("#post_outtime_" + rowid).val("");

					setTimeout(function() {
						$("#post_outtime_" + rowid).focus();
					}, 0);

					cflag	= false;
					return false;
				}

				var post_start_time	= getUnixTimeStamp(sel_date + " " + post_intime);
				var post_end_time	= getUnixTimeStamp(sel_date + " " + post_outtime);

				if (post_end_time <= post_start_time) {

					alert("Out-Time should be greater than In-Time");

					$("#post_outtime_" + rowid).val("");

					setTimeout(function() {
						$("#post_outtime_" + rowid).focus();
					}, 0);

					cflag	= false;
					return false;
				}
			}
		}

		if (validateHours(document.getElementById('daily_rate_0_' + rowid), "Regular Hours")) {

			if (document.getElementById('daily_rate_0_' + rowid).value != "") {

				date_rowid.push(rowid);
			}
		}
	});

	if (cflag) {

		row_count	= date_rowid.length;

		for (var i = 0; i < row_count; i++) {

			key1	= parseInt(date_rowid[i]);

			pre_date		= $("#daily_dates_" + key1).val();
			pre_intime		= $("#pre_intime_" + key1).val();
			pre_outtime		= $("#pre_outtime_" + key1).val();
			post_intime		= $("#post_intime_" + key1).val();
			post_outtime		= $("#post_outtime_" + key1).val();
					
			if(pre_intime != ""){
				pre_str_intime		= getUnixTimeStamp(pre_date + " " + pre_intime);
			}else{
				pre_str_intime		= "";
			}
			if(pre_outtime != ""){
				pre_str_outtime		= getUnixTimeStamp(pre_date + " " + pre_outtime);
			}else{
				pre_str_outtime		= "";
			}
			if(post_intime != ""){
				post_str_intime		= getUnixTimeStamp(pre_date + " " + post_intime);
			}else{
				post_str_intime		= "";
			}
			if(post_outtime != ""){
				post_str_outtime	= getUnixTimeStamp(pre_date + " " + post_outtime);
			}else{
				post_str_outtime	= "";
			}

			min_datetime1 = pre_str_intime;
			max_datetime1 = pre_str_outtime;
			min_datetime2 = post_str_intime;
			max_datetime2 = post_str_outtime;

			for (var j = 0; j < row_count; j++) {

				key2	= parseInt(date_rowid[j]);

				if (key1 != key2) {

					nex_date	= $("#daily_dates_" + key2).val();

					if (pre_date == nex_date) {

						nex_pre_intime		= $("#pre_intime_" + key2).val();
						nex_pre_outtime		= $("#pre_outtime_" + key2).val();
						nex_post_intime		= $("#post_intime_" + key2).val();
						nex_post_outtime	= $("#post_outtime_" + key2).val();

						if(nex_pre_intime != ""){
							nex_pre_str_intime	= getUnixTimeStamp(nex_date + " " + nex_pre_intime);
						}else{
							nex_pre_str_intime	= "";
						}
						if(nex_pre_outtime != ""){
							nex_pre_str_outtime	= getUnixTimeStamp(nex_date + " " + nex_pre_outtime);
						}else{
							nex_pre_str_outtime	= "";
						}
						if(nex_post_intime != ""){
							nex_post_str_intime	= getUnixTimeStamp(nex_date + " " + nex_post_intime);
						}else{
							nex_post_str_intime	= "";
						}
						if(nex_post_outtime != ""){
							nex_post_str_outtime	= getUnixTimeStamp(nex_date + " " + nex_post_outtime);
						}else{
							nex_post_str_outtime	= "";
						}
						
						if (checkTimeWithinRange(min_datetime1, max_datetime1,nex_pre_str_intime) || checkTimeWithinRange(min_datetime2, max_datetime2,nex_pre_str_intime) || checkTimeWithinRange(min_datetime1, max_datetime1,nex_pre_str_outtime) || checkTimeWithinRange(min_datetime2, max_datetime2,nex_pre_str_outtime) || checkTimeWithinRange(min_datetime1, max_datetime1,nex_post_str_intime) || checkTimeWithinRange(min_datetime2, max_datetime2,nex_post_str_intime) || checkTimeWithinRange(min_datetime1, max_datetime1,nex_post_str_outtime) || checkTimeWithinRange(min_datetime2, max_datetime2,nex_post_str_outtime) || checkTimeForSameDateTime(min_datetime1, max_datetime1, nex_pre_str_intime, nex_pre_str_outtime) || checkTimeForSameDateTime(min_datetime1, max_datetime1, nex_post_str_intime, nex_post_str_outtime) || checkTimeForSameDateTime(min_datetime2, max_datetime2, nex_pre_str_intime, nex_pre_str_outtime) || checkTimeForSameDateTime(min_datetime2, max_datetime2, nex_post_str_intime, nex_post_str_outtime)) {
							msg = "The hours are overlapped with the same date. Please re-enter the hours for "+nex_date;
							cflag = false;
						}
					}
				}
			}
		}

		if (msg != "") {
			alert(msg);
			return false;
		}
	}

	return cflag;
}

/* This function is used to check Time In and Time Out for Start & End in muliple rows for same date and same time */
function checkTimeForSameDateTime(prestarttime, preendtime, poststarttime, postendtime) {
	if (prestarttime != "" && preendtime != "" && poststarttime != "" && postendtime != "") {
		if (prestarttime == poststarttime && preendtime == postendtime) {
			return true;
		}
	}

	return false;
}

function checkTimeWithinRange(starttime, endtime, timetocheck) {
	if (starttime != "" && endtime != "") {
		if (timetocheck > starttime && timetocheck < endtime) {
			return true;
		}
	}

	return false;
}

function getUnixTimeStamp(sel_date) {

	return Date.parse(sel_date);
}

function checkForSpecialChars(field) {

	var str	= field.value;

	for (var i = 0; i < str.length; i++) {

		var ch	= str.substring(i, i + 1);

		if ((ch=="^" || ch=="|" )) {

			return true;
		}
	}

	return false;
}

function validateHours(field, name) {

	var str	= field.value;

	if (isNaN(str) || str.substring(0,1)=="-" || str.substring(0,1)=="+") {

		alert(name + " field accepts numbers and decimals only. Enter a valid time value.");

		field.focus();

		return false;
	}

	return true;
}

function getObjValue(obj) {

	if (obj.value == "")
	return "";
	else
	return obj.value;
}

function timesheetSubmitCheckAlert() {

	var obj		= document.getElementById("dynsndiv");
	var obj1	= document.getElementById("SaveAlert");

	var v_width	= 710;
	var v_heigth	= 424;

	var top		= (window.screen.availHeight-v_heigth)/2;
	var left	= (window.screen.availWidth-v_width)/2;

	with (obj) {


		
		style.top = "0px";
		style.left = "0px";
		if(navigator.appName == 'Microsoft Internet Explorer'){
			style.width  = window.document.body.clientWidth;
			style.height = window.document.body.clientHeight;
		} else {
			style.width  = "100%";
			style.height = "100%";
		}
		
		style.zIndex = "99999";
		style.position = "absolute";
		style.filter = "alpha(opacity=50)";
		style.backgroundColor = "#000000";
		style.opacity = ".5";
		style.display = "block";
	}

	with (obj1) {

		style.position	= "absolute";
		style.top		= top+"px";
		style.left		= left+"px";
		style.zIndex	= 99999;
		style.display	= "block";
		style.visibility	= "visible";
		if(mode != 'edit'){
			var displayHTMCode = '<table style="width:100%; " border="0"><tr valign="middle"><td width="99%" style="text-align:center;"><font style="font-family:Arial, Helvetica, sans-serif; size=12px"; ></font><br /><br /><br /><img src=\'/BSOS/images/preloader.gif\' align=middle /></td><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"></tr><tr valign="middle" height="5px"><td></td></tr></tr></table>'; 
		}else{
			
			var displayHTMCode = '<table style="width:100%; " border="0"><tr valign="middle"><td width="99%" style="text-align:center;"><font style="font-family:Arial, Helvetica, sans-serif; size=12px"; ></font><br /><br /><img src=\'/BSOS/images/preloader.gif\' align=middle /></td><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="buttonAssoc" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></tr></table>';
		}
		obj1.innerHTML	= displayHTMCode;
	}
}

function getConfirmAlert(status) {

	var form	= document.sheet;
	var act		= form.aa.value;

	var v_heigth	= 300;
	var v_width	= 600;

	var top1	= (window.screen.availHeight-v_heigth)/2;
	var left1	= (window.screen.availWidth-v_width)/2;

	switch (status) {

		case '1':	singleTimesheetSubmit();
				break;

		case '2':	form.submit();
				break;

		case '3':	multipleTimesheetOptNo();
				break;

		case '-1':
		
		$('#timesubmit').off('keydown');
		break;
	}

	document.getElementById("dynsndiv").style.display	= "none";
	document.getElementById("SaveAlert").style.display	= "none";
}

function getValidateTimesheet(timesheetType) {

	var dateRangeFilled	= "NO";
	var timesheetStatusTxt	= DynCls_Ajx_responseTxt;

	window.onbeforeunload	= null;

	if (timesheetType == "single") {

		if (timesheetStatusTxt != 0) {

			var rspsArr	= timesheetStatusTxt.split("|");
			var lockdownflag = document.sheet.lockdown_flag.value;

			if (document.sheet.module.value == "MyProfile")
				var alMsg ='<tr><td class="alert-time-msg"><b>A Timesheet for the below dates already exists.</b></td></tr><tr><td class="alert-time-msg"><div style="height:auto; overflow:auto">'+rspsArr[0]+'</div></td></tr>';
			else
				var alMsg ='<tr><td class="alert-time-msg"><b>Timesheet exists for this employee with below dates.</b></td></tr><tr><td class="alert-time-msg"><div style="height:auto; overflow:auto">'+rspsArr[0]+'</div></td></tr>';

			if (lockdownflag == "dont_allow_duplicate")
			{
				var actionStr = (document.sheet.aa.value == "submit")?'Submit':'Save';
				var htmDisplay = '<div class="alert-ync-title-group-exe">Confirmation</div><table style="width:100%; " border="0">'+alMsg+'<tr><td class="alert-time-msg">Click <b>Cancel</b> to go back and <b>'+actionStr+'</b> the timesheet for dates other than the above dates.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmAlert(\'1\');"  class="buttonAssoc" style="display:none;"/> &nbsp; <input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="buttonAssoc" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
			}
			else
			{
				var htmDisplay = '<div class="alert-ync-title-group-exe">Confirmation</div><table style="width:100%; " border="0">'+alMsg+'<tr><td class="alert-time-msg">Click on <b>Yes</b> to continue creating another timesheet for the same date range or <b>Cancel</b> to return to screen.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmAlert(\'1\');"  class="buttonAssoc" /> &nbsp; <input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="buttonAssoc" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
			}			

			document.getElementById("SaveAlert").innerHTML = htmDisplay;
			
			return;

		} else {

			singleTimesheetSubmit();
		}

	} else {

		if (timesheetStatusTxt != 0) {

			var rspsArr	= timesheetStatusTxt.split("|");

			if (rspsArr[4] != '')
			document.sheet.chksnoid.value = rspsArr[4];

			var htmDisplay	= '<div style="height:25px; background-color:#00B9F2; font-family:Tahoma, Helvetica, sans-serif; font-size:small; font-weight:bold; color:Captiontext; vertical-align:middle; text-align:left;"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr valign="middle"><td id="captionTd" style="width:96%; padding:5px; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; color:#000000;" valign="middle"><b>Confirmation</b></td></tr></table></div><table style="width:100%; height:95%; background-color:#FFFFFF;" border="0"> <tr><td class="alert-time-msg"><b>Timesheets exists for the below employees for this date range ('+rspsArr[1]+').</b></td></tr><tr><td class="alert-time-msg"><div style="overflow:auto">'+rspsArr[0]+'</div></td></tr><tr><td class="alert-time-msg">Click on <b>Yes</b> to continue creating another timesheet for the same date range or <b>Cancel</b> to return to screen.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmAlert(\'2\');"  class="time-alert-button" /> &nbsp; <input type="button" name="btnConfirmNo" id="btnConfirmNo" value="No" onClick="javascript: getConfirmAlert(\'3\');"  class="time-alert-button" /> &nbsp;<input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="time-alert-button" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';

			document.getElementById("SaveAlert").innerHTML	= htmDisplay;
			return;

		} else {

			form	= document.sheet;
			form.submit();
		}
	}
}

function singleTimesheetSubmit() {

	var form		= document.sheet;
	var edit_mode	= form.edit_mode.value;

	if (edit_mode == "edit") {

		form.action	= "/include/edit_savetime.php";

	} else {

		form.action	= "/include/savetime.php";
	}

	form.submit();
}

/* Function related to MyProfile Module */

function reSubmitTimesheet()
{
	form=document.sheet;

	/* Show Disclaimer - For ESS User When Disclaimer Content Exists On Timesheet Submission */
	dis_empUsernames = form.empUsernames.value;
	dis_parid = form.addr.value;
	dis_checking_from = form.checking_from.value;
	dis_checking_to = form.checking_to.value;
	dis_getdates = form.getdates.value;

	if(checkObject(document.getElementById('disclaimer_content')) && document.getElementById('disclaimer_content').innerHTML != ""){
		PopMsgHeadArr['timesheet_disclaimer']='Disclaimer';
		PopMsgFLineArr['timesheet_disclaimer']="";
		PopMsgQueArr['timesheet_disclaimer']="";
		PopMsgSLineArr['timesheet_disclaimer']="";
		PopMsgBtnTxtArr['submit']='Submit';
		PopMsgBtnValArr['submit']='submit';
		/* Shows Disclaimer Content in Lightbox */	
		PopMsgExtMsgArr['timesheet_disclaimer']="<table border=\"0\" cellpadding=\"2\" cellspacing=\"1\" width=\"98%\"><tr><td><div  id='disclaimer_container' style='overflow-y: scroll;height: 250px'>"+document.getElementById('disclaimer_content').innerHTML+"</div></td></tr></table>";
		display_Dynmic_Message('timesheet_disclaimer','cancel','submit','','displayDisclaimerResubmitAlert');
	}else{
		var ts_par_id 	= form.addr.value;
		var ts_status	= form.timestatus.value;
		var ts_snos_str = form.ts_snos_str.value;
	
		var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id+"&ts_status="+ts_status;
		var url = "/BSOS/Include/getAsgn.php";
		DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusMyProfileCallBack()");
		return;	

		return;
	}
}
function AjaxTimesheetLockingStatusMyProfileCallBack()
{
	var AjaxRes=DynCls_Ajx_responseTxt.split("|");
	if(AjaxRes[0]==1)
	{
		closeMessage();			
		alert('Timesheet has been modified by '+AjaxRes[2]+'.Please click on OK to close the timesheet.');
		window.opener.location.reload(true);		
		window.close();
		return;
	}
	else{
		//Call this for already submitted validation for date range..
		timesheetSubmitCheckAlert(form);
		
		var empUsernames = form.empUsernames.value;
		var parid	 = form.addr.value;
		
		var checking_from = form.checking_from.value;
		var checking_to	= form.checking_to.value;
		var getdates 	= form.getdates.value;
		
		var content = "rtype=getTimesheetStatus&multiple=NO&moduleName=MyProfile&dateRangeFilled=NO&timeEdit=YES&parid="+parid+"&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates;
		
		var url = "/BSOS/Include/getAsgn.php";
		DynCls_Ajax_result(url,'rtype',content,"getValidateTimesheetMyProfile('view')");
		return;
	}
}
function getValidateTimesheetMyProfile(act) {

	var timesheetStatusTxt	= DynCls_Ajx_responseTxt;

	if (timesheetStatusTxt != 0) {

		var rspsArr	= timesheetStatusTxt.split("|");

		if (act == "view") {

			var cnfromNumber = 2;

		} else {

			var cnfromNumber = 1;
		}

		var alMsg ='<tr><td class="alert-time-msg"><b>A Timesheet for the below dates already exists.</b></td></tr><tr><td class="alert-time-msg"><div style="height:auto; overflow:auto">'+rspsArr[0]+'</div></td></tr>';

		var lockdownflag = document.sheet.lockdown_flag.value;
		if (lockdownflag == "dont_allow_duplicate")
		{
			var actionStr = 'Submit';
			var htmDisplay = '<div class="alert-ync-title-group-exe">Confirmation</div><table style="width:100%; " border="0">'+alMsg+'<tr><td class="alert-time-msg">Click <b>Cancel</b> to go back and <b>'+actionStr+'</b> the timesheet for dates other than the above dates.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmAlert(\'1\');"  class="buttonAssoc" style="display:none;"/> &nbsp; <input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="buttonAssoc" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
		}
		else{
			var htmDisplay = '<div class="alert-ync-title-group-exe">Confirmation</div><table style="width:100%; " border="0">'+alMsg+'<tr><td class="alert-time-msg">Click on <b>Yes</b> to continue creating another timesheet for the same date range or <b>Cancel</b> to return to screen.</td></tr><tr valign="middle" height="5px"><td></td></tr><tr valign="middle"><td width="99%" style="text-align:center;"><input type="button" name="btnConfirmYes" id="btnConfirmYes" value="Yes" onClick="javascript: getConfirmAlert('+cnfromNumber+');"  class="buttonAssoc" /> &nbsp; <input type="button" name="btnConfirmCancel" id="btnConfirmCancel" value="Cancel" onClick="javascript: getConfirmAlert(\'-1\');" class="buttonAssoc" />&nbsp; </td></tr><tr valign="middle" height="5px"><td></td></tr></table>';
		}


		document.getElementById("SaveAlert").innerHTML = htmDisplay;

		return;

	} else {

		if (act == "view") {

			form = document.sheet;
			form.action="/BSOS/MyProfile/Timesheet/saveStimesheet.php";
			form.submit();

		} else {

			form = document.sheet;
			form.submit();
		}
	}
}

function deleteTimesheet() {

	form	= document.sheet;

	if (checkObject(document.getElementById('module'))) {

		if (document.getElementById('module').value == 'MyProfile') {

			if (confirm("Do you want to delete this Time Sheet?")) {

				document.getElementById("act").value = "deletesheet";
				form.action="/BSOS/MyProfile/Timesheet/timesheets_profile.php";
				form.submit();
			}
		}
	}
}

function checkObject(obj) {

	return obj && obj !== "null" && obj !== "undefined";
}

function displayDisclaimerAlert(retstatus)
{
     switch(retstatus)
     {
    	case 'cancel':	
		var ele = document.getElementById("timesubmit");
		ele.href = elehref;	
	    	break;
    	case 'submit':
		var form	= document.forms[0];		
		var edit_mode 	= form.edit_mode.value;		
		if (edit_mode == 'edit') {
			var ts_par_id 	= form.addr1.value;
			var ts_status	= form.ts_status.value;
			var ts_snos_str = "";
						
			$(".chremove").each(function(){
				if (ts_snos_str=="") {
					ts_snos_str = $(this).val();
				}
				else{
					ts_snos_str += ","+$(this).val();
				}
			});
		
			var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id+"&ts_status="+ts_status;
			var url = "/BSOS/Include/getAsgn.php";
			DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusDisclaimerCallBack()");
			return;
		}
		else{
			submitTimesheetWithDisclaimer();
		}
		break;
     }
}
function AjaxTimesheetLockingStatusDisclaimerCallBack()
{
	var AjaxRes=DynCls_Ajx_responseTxt.split("|");
	form	= document.sheet;
	if(AjaxRes[0]==1)
	{			
		alert('Timesheet has been modified by '+AjaxRes[2]+'.Please click on OK to close the timesheet.');
		window.opener.location.reload(true);		
		window.close();
		return;
	}
	else{
		//Call this for already submitted validation for date range..		
		submitTimesheetWithDisclaimer();
	}
}
function submitTimesheetWithDisclaimer()
{
	closeMessage();
	form=document.sheet;

	var lockdownflag 	= form.lockdown_flag.value;
	var ts_par_id 	= form.addr1.value;	
	var edit_mode 	= form.edit_mode.value;
	var par_id_str = "";
	if (edit_mode == 'edit') {
	   par_id_str = "&parid="+ts_par_id;
	}	
	//Call this for already submitted validation for date range..
	timesheetSubmitCheckAlert(form);

	var url		= "/BSOS/Include/getAsgn.php";
	var content	= "rtype=getTimesheetStatus&multiple=NO&dateRangeFilled=NO&moduleName="+dis_moduleName+"&checking_from="+dis_checking_from+"&checking_to="+dis_checking_to+"&empUsernames="+dis_empUsernames+"&getdates="+dis_getdates+"&lockdown_flag="+lockdownflag+par_id_str;

	DynCls_Ajax_result(url, 'rtype', content, "getValidateTimesheet('single')");
	return;
}
function displayDisclaimerResubmitAlert(retstatus)
{
     switch(retstatus)
     {
    	case 'cancel':	break;
    	case 'submit':
		var form	= document.forms[0];		
		var ts_par_id 	= form.addr.value;
		var ts_status	= form.timestatus.value;
		var ts_snos_str = form.ts_snos_str.value;
	
		var content 	= "rtype=getTimesheetLockingStatus&ts_snos_str="+ts_snos_str+"&ts_par_id="+ts_par_id+"&ts_status="+ts_status;
		var url = "/BSOS/Include/getAsgn.php";
		DynCls_Ajax_result(url,'rtype',content,"AjaxTimesheetLockingStatusDisclaimerResubmitCallBack()");
		return;
		break;
     }
}
function AjaxTimesheetLockingStatusDisclaimerResubmitCallBack()
{
	var AjaxRes=DynCls_Ajx_responseTxt.split("|");
	if(AjaxRes[0]==1)
	{
		closeMessage();	
		alert('Timesheet has been modified by '+AjaxRes[2]+'.Please click on OK to close the timesheet.');
		window.opener.location.reload(true);		
		window.close();
		return;
	}
	else{
		//Call this for already submitted validation for date range..		
		reSubmitTimesheetWithDisclaimer();
	}
}

function reSubmitTimesheetWithDisclaimer()
{
	closeMessage();	
	form=document.sheet;
	timesheetSubmitCheckAlert(form);

	var content = "rtype=getTimesheetStatus&multiple=NO&moduleName=MyProfile&dateRangeFilled=NO&timeEdit=YES&parid="+dis_parid+"&checking_from="+dis_checking_from+"&checking_to="+dis_checking_to+"&empUsernames="+dis_empUsernames+"&getdates="+dis_getdates;

	var url = "/BSOS/Include/getAsgn.php";
	DynCls_Ajax_result(url,'rtype',content,"getValidateTimesheetMyProfile('view')");
	return;
}

function getDataOnDate(comprowid) {

	if (module != 'MyProfile') {

		if (mode == 'edit')
		var empacc	= $("#empnames_myprofile").val();
		else{
			var empacc	= $(".drpdwnaccc option:selected").val();
			$("#empnames_oldvalue").val(empacc);
		}		

	} else if (module == 'MyProfile') {
	
		var empacc = $("#empnames_myprofile").val();
	}

	var client_id	= 0;
	if (module == "Client") {

		client_id	= $("#client_id").val();
	}

	var rowid	= comprowid.split("_").pop(-1);
	var dateval	= $("#"+comprowid).val();
	var url		= "/include/loadtimedata.php?date="+dateval+"&empacc="+empacc+"&rowid="+rowid+"&mod=dates&module="+module+"&client_id="+client_id+"&ts_type=TimeInTimeOut";

	// START - SEVENTH DAY RULE SPECIFIC
	// Updates the dyna_dates array having dates, rowid and dayname
	if (seventhdayrule_flag != 0) {
		if(findInArray(dyna_dates, rowid,0) == true){
			removeRowId(rowid);
			//dyna_dates.push([rowid, $("#daily_dates_" + rowid).val(), parseDate($("#daily_dates_" + rowid).val()).getDay()]);	
		}		
	}		
	// END - SEVENTH DAY RULE SPECIFIC
	
	$.get(url, function(data) {

		if (!data) {

			alert("There are no assignments available on this date to create Timesheet");
			return;

		} else {

			$("#span_"+rowid).html(data);
			
			//binding select2 after pushing the new html content
			var customSelectElement = $('#MainTable #span_'+rowid+' select');
			bindSelect2(customSelectElement);
			
			$("#daily_classes_"+rowid).val(0);
			$("#pre_intime_"+rowid).val("");
			$("#pre_intime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
			$("#pre_outtime_"+rowid).val("");
			$("#pre_outtime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
			$("#break_hours_"+rowid).val("");
			$("#post_intime_"+rowid).val("");
			$("#post_intime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
			$("#post_outtime_"+rowid).val("");
			$("#post_outtime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
			$("#daily_rate_0_"+rowid).val("");
			$("#daily_rate_1_"+rowid).val("");
			$("#daily_rate_2_"+rowid).val("");
			$("#singledaytotalhrs_"+rowid).val("");

			if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {

				calculateweekhours();

			} else {

				calculateregularhours(rowid);
			}

			$("#daily_assignemnt_"+rowid).on("change", function() {

				getDataOnAssignment("daily_assignemnt_"+rowid);
			});

			getTimeInTimeOutRates("daily_assignemnt_"+rowid);
		}
	});
	
	// START - SEVENTH DAY RULE SPECIFIC
	// Updates the dyna_dates array having dates, rowid and dayname
	//if (seventhdayrule_flag) {
	//	apply_rule(rowid);
	//}		
	// END - SEVENTH DAY RULE SPECIFIC
}

function getDataOnAssignment(asgn_rowid) {

	var rowid	= asgn_rowid.split("_").pop(-1);

	$("#daily_classes_"+rowid).val(0);
	$("#pre_intime_"+rowid).val("");
	$("#pre_intime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
	$("#pre_outtime_"+rowid).val("");
	$("#pre_outtime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
	$("#break_hours_"+rowid).val("");
	$("#post_intime_"+rowid).val("");
	$("#post_intime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
	$("#post_outtime_"+rowid).val("");
	$("#post_outtime_"+rowid).inputmask({ alias: "datetime", placeholder: "HH:MM AM", inputFormat: "hh:MM TT", insertMode: false, showMaskOnHover: true, hourFormat: 12 });
	$("#daily_rate_0_"+rowid).val("");
	$("#daily_rate_1_"+rowid).val("");
	$("#daily_rate_2_"+rowid).val("");
	$("#singledaytotalhrs_"+rowid).val("");

	if (ruletype_flag == "weekrule" && wk_ruleflag == "Y") {

		calculateweekhours();

	} else {

		calculateregularhours(rowid);
		
	}

	getTimeInTimeOutRates(asgn_rowid);
}

function getTimeInTimeOutRates(asgnid) {

	if (module != 'MyProfile') {

		if (mode == 'edit')
		var empacc	= $("#empnames_myprofile").val();
		else{
			var empacc	= $(".drpdwnaccc option:selected").val();
			$("#empnames_oldvalue").val(empacc);
		}

	} else if (module == 'MyProfile') {

		var empacc = $("#empnames_myprofile").val();
	}

	var rowid	= asgnid.split("_").pop(-1);
	var asgnval	= $("#"+asgnid).children(":selected").attr("id");
	var asgntext	= asgnval.split("-");
	var url		= "/include/loadtimedata.php?mod=asgn&empacc="+empacc+"&asgn="+asgntext[0]+"&rowid="+rowid;
	
	// START - SEVENTH DAY RULE SPECIFIC
	// Updates the dyna_dates array having dates, rowid and dayname
	if (seventhdayrule_flag != 0) {
		if(findInArray(dyna_dates, rowid,0) == true){
			removeRowId(rowid);			
		}
		
		rule_condition3 = chk_state_of_asgn();
		if(rule_condition3){
			apply_rule(rowid);	
		}else{
			if (sixth_day_rowid > -1) {
				// Recalculate Sixth Day	
				reCalculateTime(sixth_day_rowid);
			}
			if (seventh_day_rowid > -1) {
				// Recalculate Seventh Day
				reCalculateTime(seventh_day_rowid);	
			}
		}
	}		
	// END - SEVENTH DAY RULE SPECIFIC

	$.get(url, function(data) {

		var dataArr	= data.split("|");
		var ratecount	= dataArr.length;

		for (i=0; i<ratecount; i++) {

			var dataArrinternal	= dataArr[i].split(",");

			if (dataArrinternal[1] == "Y") {

				$("#MainTable input[id="+dataArrinternal[0]+"]").attr("readonly", true);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr("disabled", false);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val("");

			} else {

				if (checkObject(dataArrinternal[0]) && checkObject(dataArrinternal[2])) {

					if ((dataArrinternal[0].indexOf("daily_rate") > -1) || (dataArrinternal[2].indexOf("daily_rate") > -1)) {

						$("#MainTable input[id="+dataArrinternal[0]+"]").attr("readonly", true);
						$("#MainTable input[id="+dataArrinternal[2]+"]").attr("disabled", true);
						$("#MainTable input[id="+dataArrinternal[2]+"]").attr("checked", false);
						$("#MainTable input[id="+dataArrinternal[0]+"]").val("");
					}
				}
			}

			if (dataArrinternal[3] == "Y") {

				$("#MainTable input[id="+dataArrinternal[2]+"]").attr("disabled", false);
				$("#MainTable input[id="+dataArrinternal[2]+"]").attr("checked", true);
				$("#MainTable input[id="+dataArrinternal[0]+"]").val("");

			} else {

				if (checkObject(dataArrinternal[0]) && checkObject(dataArrinternal[2])) {

					if ((dataArrinternal[0].indexOf("daily_rate") > -1) || (dataArrinternal[2].indexOf("daily_rate") > -1)) {

						$("#MainTable input[id="+dataArrinternal[2]+"]").attr("disabled", true);
						$("#MainTable input[id="+dataArrinternal[2]+"]").attr("checked", false);
						$("#MainTable input[id="+dataArrinternal[0]+"]").val("");
					}
				}
			}
		}
	});
}

function hideTaskDetailsTextBox(rowid)
{
	var id = rowid;

	if ($.trim($("#taskTB_"+id).val()) == '') {

		this.value = (this.defaultValue ? this.defaultValue : '');

		$("#textlabel_"+id).html("");
		$("#taskTB_"+id).val("");

	} else {

		$("#textlabel_"+id).html($("#taskTB_"+id).val());
	}

	$("#taskTB_"+id).hide();
	$("#textlabel_"+id).show();

	$("#taskTB_"+rowid).parent('td').parent('tr').removeAttr("style");
}

/* Reference -  forceRoundTime property in jquery.timepicker.js
 * Rounds time up or down to a nearest step value
 * param time  - input time in this format only "03/15/2014 12:14 PM" 
 * param step - increment value in minutes - 5 mins , 10 mins or 15 mins
*/
function getRoundoffTime(time, step) {

	format = "h:i A";

	var inputtime = new Date(time);

	minutes = inputtime.getMinutes();

	hours = inputtime.getHours();

	// Converts input time in seconds

	seconds = minutes*60+hours*60*60; 

	var offset = seconds % (step*60); // step is in minutes

	if (offset >= step*30) {
		// if offset is larger than a half step, round up
		seconds += (step*60) - offset;
	} else {
		// round down
		seconds -= offset;
	}

	var output = convertSec2Time(seconds, format);

	// Exception - 11:59 PM (23 hours) rounded to 12:00 AM (next day) which is incorrect as per requirement
	// In such cases, rounded to 11:59 PM and not 12:00 AM	
	if (hours == "23" && output == "12:00 AM") {
		output =  "11:59 PM";
	}

	return output;
}
function getRoundOffMinutes_time(hr_min, step) {
	// Converts input time in seconds
	seconds = hr_min*60*60;
	if(step != 0 && step != ''){
	var offset = seconds % (step*60); // step is in minutes
	
	if (offset >= step*30) {
		// if offset is larger than a half step, round up
		seconds += (step*60) - offset;
	} else {
		// round down
		seconds -= offset;
	}
	var output = (seconds/60)/60;

	// Exception - 11:59 PM (23 hours) rounded to 12:00 AM (next day) which is incorrect as per requirement
	// In such cases, rounded to 11:59 PM and not 12:00 AM	
	/*if (hours == "23" && output == "12:00 AM") {
		output =  "11:59 PM";
	}*/
	return output;
	}
	else {
		return hr_min;
	}
}
// Converts time in seconds to specified time format 
function convertSec2Time(seconds, format)
{
	if (seconds === null) {
		return;
	}
	
	baseDate = new Date(1970, 1, 1, 0, 0, 0);
	var ONE_DAY = 86400;

	var time = new Date(baseDate.valueOf() + (seconds*1000));
	
	var output = '';
	var hour, code;

	for (var i=0; i<format.length; i++) {

		code = format.charAt(i);
		switch (code) {

			case 'a':
				output += (time.getHours() > 11) ? 'pm' : 'am';
				break;

			case 'A':
				output += (time.getHours() > 11) ? 'PM' : 'AM';
				break;

			case 'g':
				hour = time.getHours() % 12;
				output += (hour === 0) ? '12' : hour;
				break;

			case 'G':
				output += time.getHours();
				break;

			case 'h':
				hour = time.getHours() % 12;

				if (hour !== 0 && hour < 10) {
					hour = '0'+hour;
				}

				output += (hour === 0) ? '12' : hour;
				break;

			case 'H':
				hour = time.getHours();
				if (seconds === ONE_DAY) hour = 24;
				output += (hour > 9) ? hour : '0'+hour;
				break;

			case 'i':
				var minutes = time.getMinutes();
				output += (minutes > 9) ? minutes : '0'+minutes;
				break;

			case 's':
				seconds = time.getSeconds();
				output += (seconds > 9) ? seconds : '0'+seconds;
				break;

			default:
				output += code;
		}
	}
	return output;
}

function calculateweekhours() {

	// GLOBAL VARIABLES FROM PAYROLL SETUP [MAX.REGULAR & OVERTIME HOURS PER WEEK]
	wk_max_reg_hrs	= formatNumber(wk_max_reg_hrs);
	wk_max_ovt_hrs	= formatNumber(wk_max_ovt_hrs);

	var reg_max_hours	= parseFloat(wk_max_reg_hrs);
	var ovt_max_hours	= parseFloat(wk_max_ovt_hrs);

	var row_id;
	var inputid;

	var rg_total;
	var ot_total;

	var fmt_reg_hrs;
	var fmt_ovt_hrs;
	var fmt_dbt_hrs;

	var fmt_reg_ttl;
	var fmt_ovt_ttl;
	var fmt_dbt_ttl;
	var fmt_total_hours;

	var max_reg_rowid;
	var max_ovt_rowid;

	var reg_total	= 0.00;
	var ovt_total	= 0.00;
	var dbt_total	= 0.00;

	var regular_hours	= 0.00;
	var overtime_hours	= 0.00;
	var doubletime_hours	= 0.00;
	var weektotal_hours	= 0.00;

	var fmt_zeroval	= formatNumber(0);
	
	
//get the delete rowids and removes from rowid_hours_for_each_list JSON Object
	deleted_rowids = $("#deleted_rowids").val();
	if(deleted_rowids != ""){
		var deleted_rowid_list = deleted_rowids.split(',');
		if(deleted_rowid_list.length > 0){
			
			for(var i=0; i<deleted_rowid_list.length; i++){
			
				delete rowid_hours_for_each_list[deleted_rowid_list[i]];
				
			}
			
			
		}
	}
	// IDENTIFYING ROW ID WHERE SUM OF REGULAR HOURS(TOTAL) IS GREATER OR EQUAL TO MAX.REGULAR HOURS PER WEEK
	max_reg_rowid	= getRowIdForMaxRegularHours(reg_max_hours);

	// GETTING REGULAR HOURS TOTAL
	rg_total	= getRegularHoursTotal();
	rowid_after_reg_max_rowid = 0;
	
	if (max_reg_rowid != -1) {

		$(".rowRegularHours").each(function() {

			att_id	= $(this).attr("id");
			reg_id	= parseInt(att_id.split("_").pop(-1));

			if (reg_id >= max_reg_rowid) {

				tot_hours_day	= parseFloat(getTotalHoursForADay(reg_id));

				if (tot_hours_day > 0) {

					tot_hours_day	= formatNumber(tot_hours_day);
					//Rounding Code
					var tot_hours_day_time = tot_hours_day.split(".");
					if(tot_hours_day_time[1] =='' || tot_hours_day_time[1] == undefined){
						tot_hours_day_time[1]=0;
					}
					var tot_hours_day_mint = '0.'+tot_hours_day_time[1];
					tot_hours_day_min = getRoundOffMinutes_time(tot_hours_day_mint, time_increment);
					tot_hours_day_final = parseFloat(tot_hours_day_time[0]) + parseFloat(tot_hours_day_min);
					//Code Ends
					tot_hours_day_final= tot_hours_day_final.toFixed(2);
					
			
					
					$("#daily_rate_0_" + reg_id).val(tot_hours_day_final);
					$("#daily_rate_1_" + reg_id).val("");
					$("#daily_rate_2_" + reg_id).val("");
				}
			}
		});

	} else if (max_reg_rowid == -1) {

		$(".rowRegularHours").each(function() {

			att_id	= $(this).attr("id");
			reg_id	= parseInt(att_id.split("_").pop(-1));

			tot_hours_day	= parseFloat(getTotalHoursForADay(reg_id));

			if (rg_total <= reg_max_hours && tot_hours_day > 0) {

					tot_hours_day	= formatNumber(tot_hours_day);
					//Rounding Code
					var tot_hours_day_time = tot_hours_day.split(".");
					if(tot_hours_day_time[1] =='' || tot_hours_day_time[1] == undefined){
						tot_hours_day_time[1]=0;
					}
					var tot_hours_day_mint = '0.'+tot_hours_day_time[1];
					tot_hours_day_min = getRoundOffMinutes_time(tot_hours_day_mint, time_increment);
					tot_hours_day_final = parseFloat(tot_hours_day_time[0]) + parseFloat(tot_hours_day_min);
					//Code Ends
					tot_hours_day_final= tot_hours_day_final.toFixed(2);
				$("#daily_rate_0_" + reg_id).val(tot_hours_day_final);
				$("#daily_rate_1_" + reg_id).val("");
				$("#daily_rate_2_" + reg_id).val("");
			}
		});

		max_reg_rowid	= getRowIdForMaxRegularHours(reg_max_hours);
	}
	

	$(".rowRegularHours").each(function() {

		inputid	= $(this).attr("id");
		row_id	= parseInt(inputid.split("_").pop(-1));

		regular_hours	= $("#daily_rate_0_" + row_id).val();
		//fmt_reg_hrs	= formatNumber(regular_hours);
		fmt_reg_hrs	= regular_hours;
		if (regular_hours != "" ) {
			
			if(mode=='edit')
			{
				row_total = 0.00;
				reg_max_rowid = 0;
				
				if($(this).hasClass("rowRegularHours")){ 
					
					$.each(rowid_hours_for_each_list, function(row_id_key, row_hour) 
					{
						
						if(row_id == row_id_key )
						{
							changed_hours_val_at_current_rowid	= parseFloat(getTotalHoursForADay(row_id));
							
							//Rounding Code
							changed_hours_val_at_current_rowid = changed_hours_val_at_current_rowid.toFixed(2);
							var chvacr_time = changed_hours_val_at_current_rowid.split(".");
							if(chvacr_time[1] =='' || chvacr_time[1] == undefined){
								chvacr_time[1]=0;
							}
							var chvacr_mint = '0.'+chvacr_time[1];
							chvacr_min = getRoundOffMinutes_time(chvacr_mint, time_increment);
							chvacr_final = parseFloat(chvacr_time[0]) + parseFloat(chvacr_min);
							//Code Ends
							changed_hours_val_at_current_rowid= chvacr_final.toFixed(2);
							
							if(changed_hours_val_at_current_rowid !=  parseFloat(row_hour)){
								rowid_hours_for_each_list[row_id_key] =  changed_hours_val_at_current_rowid;
								row_hour = changed_hours_val_at_current_rowid;
							}
						}
						
					
						row_total = parseFloat(row_total) + parseFloat(row_hour);
						
						if(row_id == row_id_key )
						{
							
							reg_total = parseFloat(row_total) - parseFloat(row_hour);
							reg_total = parseFloat(reg_total) + parseFloat(regular_hours);
							return false; 
						}
						
					});
				}
			}else
			{
				var regular_hours_time = regular_hours.split(".");
					if(regular_hours_time[1] =='' || regular_hours_time[1] == undefined){
						regular_hours_time[1]=0;
					}
					var regular_hours_mint = '0.'+regular_hours_time[1];
					regular_hours_min = getRoundOffMinutes_time(regular_hours_mint, time_increment);
					regular_hours_final = parseFloat(regular_hours_time[0]) + parseFloat(regular_hours_min);
					//Code Ends
					regular_hours_final = regular_hours_final.toFixed(2);
			
				reg_total	= parseFloat(reg_total) + parseFloat(regular_hours_final);
			}
	
			if (max_reg_rowid != "-1" && max_reg_rowid > row_id) 
			{
				

				reg_hours = parseFloat(getTotalHoursForADay(row_id));
				reg_hours = formatNumber(reg_hours);
				//Rounding Code
					var reg_hours_time = reg_hours.split(".");
					if(reg_hours_time[1] =='' || reg_hours_time[1] == undefined){
						reg_hours_time[1]=0;
					}
					var reg_hours_mint = '0.'+reg_hours_time[1];
					reg_hours_min = getRoundOffMinutes_time(reg_hours_mint, time_increment);
					reg_hours_final = parseFloat(reg_hours_time[0]) + parseFloat(reg_hours_min);
					//Code Ends
					reg_hours_final = reg_hours_final.toFixed(2);
				$("#daily_rate_0_" + row_id).val(reg_hours_final);
				$("#daily_rate_1_" + row_id).val("");
				$("#daily_rate_2_" + row_id).val("");


			} else if (max_reg_rowid != "-1" && max_reg_rowid < row_id) {
				reg_hours = (getTotalHoursForADay(row_id));
				reg_hours = formatNumber(reg_hours);
				//Rounding Code
					var reg_hours_time = reg_hours.split(".");
					if(reg_hours_time[1] =='' || reg_hours_time[1] == undefined){
						reg_hours_time[1]=0;
					}
					var reg_hours_mint = '0.'+reg_hours_time[1];
					reg_hours_min = getRoundOffMinutes_time(reg_hours_mint, time_increment);
					reg_hours_final = parseFloat(reg_hours_time[0]) + parseFloat(reg_hours_min);
					//Code Ends
					reg_hours_final = reg_hours_final.toFixed(2);
				$("#daily_rate_0_" + row_id).val("");
				$("#daily_rate_1_" + row_id).val(reg_hours_final);
				$("#daily_rate_2_" + row_id).val("");
				
			} else if (max_reg_rowid != "-1" && max_reg_rowid == row_id) {
				

				if (reg_total > reg_max_hours) {
					 

					ovt_hours	= reg_total - reg_max_hours;
					reg_hours	= parseFloat(regular_hours) - parseFloat(ovt_hours);

					
					
					ovt_hours	= formatNumber(ovt_hours);
					reg_hours	= formatNumber(reg_hours);

					$("#daily_rate_0_" + row_id).val(reg_hours);
					$("#daily_rate_1_" + row_id).val(ovt_hours);
					$("#ovt_hrs_in_reg_max_row_id").val(ovt_hours);
					
				
				}


			} else {
				//Rounding Code
					var fmt_reg_hrs_time = fmt_reg_hrs.split(".");
					if(fmt_reg_hrs_time[1] =='' || fmt_reg_hrs_time[1] == undefined){
						fmt_reg_hrs_time[1]=0;
					}
					var fmt_reg_hrs_mint = '0.'+fmt_reg_hrs_time[1];
					fmt_reg_hrs_min = getRoundOffMinutes_time(fmt_reg_hrs_mint, time_increment);
					fmt_reg_hrs_final = parseFloat(fmt_reg_hrs_time[0]) + parseFloat(fmt_reg_hrs_min);
					//Code Ends
					//fmt_reg_hrs_final = formatNumber(fmt_reg_hrs_final);
					fmt_reg_hrs_final= fmt_reg_hrs_final.toFixed(2);
				$("#daily_rate_0_" + row_id).val(fmt_reg_hrs_final);
			}
		}
	});

	// GETTING REGULAR HOURS TOTAL
	rg_total	= getRegularHoursTotal();
	fmt_reg_ttl	= formatNumber(rg_total);

	
	
	ovt_hours_in_regmaxid  = 0.00;
	ovt_hours  = 0.00;
	reg_hours_total  = 0.00;
	var reg_max_rowid = $("#reg_hours_max_row_id").val();
	
	if(mode=='edit')
	{
	
		$.each(rowid_hours_for_each_list, function(row_id_key, row_hour) {
			
			row_id_key = parseInt(row_id_key);
			
			if(row_id_key <= reg_max_rowid)
			{
				reg_hours_total =  parseFloat(reg_hours_total) + parseFloat(row_hour);
				
				if (reg_hours_total > reg_max_hours) {
					
					ovt_hours	= reg_hours_total - reg_max_hours;
					
					ovt_hours	= formatNumber(ovt_hours);
					
					ovt_total = parseFloat(ovt_total) + parseFloat(ovt_hours);
					ovt_hours_in_regmaxid = ovt_hours;
					
				}
					
				
			}
		
		});
	}
	
	// IDENTIFYING ROW ID WHERE SUM OF OVERTIME HOURS(TOTAL) IS GREATER OR EQUAL TO MAX.OVERTIME HOURS PER WEEK
	max_ovt_rowid	= getRowIdForMaxOvertimeHours(ovt_max_hours,ovt_hours_in_regmaxid);
	var overtime_total = 0.00;
	ovt_max_row_id = parseInt(max_ovt_rowid);
	
	$(".rowOverTimeHours").each(function() {

		inputid	= $(this).attr("id");
		row_id	= inputid.split("_").pop(-1);
		row_id	= parseInt(row_id);
		
		regular_hours	= $("#daily_rate_0_" + row_id).val();
		overtime_hours	= $("#daily_rate_1_" + row_id).val();
		fmt_ovt_hrs	= formatNumber(overtime_hours);
		var reg_max_rowid = parseInt($("#reg_hours_max_row_id").val());
	
		if (overtime_hours != "" && overtime_hours > 0) {
			
			if(mode=='edit')
			{
				
				if($(this).hasClass("rowOverTimeHours")){
					
					
					
						$.each(rowid_hours_for_each_list, function(row_id_key, row_hour) 
						{
							row_id_key = parseInt(row_id_key);
							if(row_id == row_id_key && row_id != reg_max_rowid)
							{
							
								changed_hours_val_at_current_rowid	= parseFloat(getTotalHoursForADay(row_id));
								//Rounding Code
								changed_hours_val_at_current_rowid = changed_hours_val_at_current_rowid.toFixed(2);
								var chvacr_time = changed_hours_val_at_current_rowid.split(".");
								if(chvacr_time[1] =='' || chvacr_time[1] == undefined){
									chvacr_time[1]=0;
								}
								var chvacr_mint = '0.'+chvacr_time[1];
								chvacr_min = getRoundOffMinutes_time(chvacr_mint, time_increment);
								chvacr_final = parseFloat(chvacr_time[0]) + parseFloat(chvacr_min);
								//Code Ends
								changed_hours_val_at_current_rowid= chvacr_final.toFixed(2);
								if(changed_hours_val_at_current_rowid !=  parseFloat(row_hour)){
									rowid_hours_for_each_list[row_id_key] =  changed_hours_val_at_current_rowid;
									row_hour = changed_hours_val_at_current_rowid;
								}
									
									overtime_total = getMaxOvertimeHoursTotal(row_id);
									ovt_total =  parseFloat(overtime_total) + parseFloat(ovt_hours_in_regmaxid);
									return false; 
								
							
							}
													
						});
					
				
				}
			}else{

				ovt_total	= parseFloat(ovt_total) + parseFloat(overtime_hours);
			}
			
			
			if (max_ovt_rowid != "-1" && max_ovt_rowid > row_id) {

				$("#daily_rate_1_" + row_id).val(fmt_ovt_hrs);

			} else if (max_ovt_rowid != "-1" && max_ovt_rowid < row_id) {
				
				$("#daily_rate_1_" + row_id).val("");
				$("#daily_rate_2_" + row_id).val(fmt_ovt_hrs);

			} else if (max_ovt_rowid != "-1" && max_ovt_rowid == row_id) {
				
				
		
				if (ovt_total > ovt_max_hours) {

					dbt_hours	= ovt_total - ovt_max_hours;
					ovr_hours	= parseFloat(overtime_hours) - parseFloat(dbt_hours);

					ovr_hours	= formatNumber(ovr_hours);
					dbt_hours	= formatNumber(dbt_hours);

					$("#daily_rate_1_" + row_id).val(ovr_hours);
					$("#daily_rate_2_" + row_id).val(dbt_hours);
				}

			} else {

				$("#daily_rate_1_" + row_id).val(fmt_ovt_hrs);
			}

			if (regular_hours == "") {

				$("#daily_rate_0_" + row_id).val(fmt_zeroval);
			}
		}
		
		
	});

	// GETTING OVERTIME HOURS TOTAL
	ot_total	= getOverTimeHoursTotal();
	fmt_ovt_ttl	= formatNumber(ot_total);

	// GETTING DOUBLETIME TOTAL HOURS
	$(".rowDoubleTimeHours").each(function() {

		atb_id	= $(this).attr("id");
		dbt_id	= atb_id.split("_").pop(-1);

		regular_hours	= $("#daily_rate_0_" + dbt_id).val();
		overtime_hours	= $("#daily_rate_1_" + dbt_id).val();
		doubletime_hours= $("#daily_rate_2_" + dbt_id).val();
		fmt_dbt_hrs		= formatNumber(doubletime_hours);

		if (doubletime_hours != "") {

			dbt_total	= parseFloat(dbt_total) + parseFloat(doubletime_hours);

			$("#daily_rate_2_" + dbt_id).val(fmt_dbt_hrs);

			if (regular_hours == "") {

				$("#daily_rate_0_" + dbt_id).val(fmt_zeroval);
			}

			if (overtime_hours == "") {

				$("#daily_rate_1_" + dbt_id).val(fmt_zeroval);
			}
		}

		fmt_dbt_ttl	= formatNumber(dbt_total);
	});

	// GETTING WEEK TOTAL HOURS
	weektotal_hours	= parseFloat(fmt_reg_ttl) + parseFloat(fmt_ovt_ttl) + parseFloat(fmt_dbt_ttl);
	fmt_total_hours	= formatNumber(weektotal_hours);

	$("#final_regular_hours").html(fmt_reg_ttl);
	$("#final_overtime_hours").html(fmt_ovt_ttl);
	$("#final_doubletime_hours").html(fmt_dbt_ttl);
	$("#final_total_hours").html(fmt_total_hours);
}

function getTotalHoursForADay(row_id) {

	var sel_date		= $("#daily_dates_" + row_id).val();
	var pre_intime		= $("#pre_intime_" + row_id).val();
	var pre_outtime		= $("#pre_outtime_" + row_id).val();
	var post_intime		= $("#post_intime_" + row_id).val();
	var post_outtime	= $("#post_outtime_" + row_id).val();

	var tot_pre_hours	= 0.00;
	var tot_post_hours	= 0.00;
	var overall_hours	= 0.00;

	if (pre_intime != "" && pre_outtime != "") {

		var pre_in_str	= sel_date + " " + pre_intime;
		var pre_out_str	= sel_date + " " + pre_outtime;

		var pre_start_time	= Date.parse(pre_in_str);
		var pre_end_time	= Date.parse(pre_out_str);

		// ROUNDS THE TIME BASED ON TIME INCREMENT
		if (time_increment !== "0") {

			pre_in_str	 = sel_date+" "+ pre_intime;	
			pre_out_str	 = sel_date+" "+ pre_outtime;

			pre_start_time	= Date.parse(pre_in_str);
			pre_end_time	= Date.parse(pre_out_str);
		}

		tot_pre_hours	= calculateTimeDifference(pre_start_time, pre_end_time);
	}

	if (post_intime != "" && post_outtime != "") {

		var post_in_str		= sel_date + " " + post_intime;
		var post_out_str	= sel_date + " " + post_outtime;

		var post_start_time	= Date.parse(post_in_str);
		var post_end_time	= Date.parse(post_out_str);

		// ROUNDS THE TIME BASED ON TIME INCREMENT
		if (time_increment !== "0") {

			post_in_str	= sel_date+" "+post_intime;
			post_out_str	= sel_date+" "+post_outtime;

			post_start_time	= Date.parse(post_in_str);
			post_end_time	= Date.parse(post_out_str);
		}

		tot_post_hours	= calculateTimeDifference(post_start_time, post_end_time);
	}

	overall_hours	= parseFloat(tot_pre_hours) + parseFloat(tot_post_hours);
	overall_hours	= formatNumber(overall_hours);

	return overall_hours;
}

function getRowIdForMaxRegularHours(reg_max_hours) {

	var reg_max_rowid	= -1;
	var regular_total	= 0.00;
	var overtime_hours	= 0.00;
	
	$(".rowRegularHours").each(function() {

		att_id		= $(this).attr("id");
		reg_id		= parseInt(att_id.split("_").pop(-1));
		rg_hours	= $("#daily_rate_0_" + reg_id).val();
		if(rg_hours ==""){
			rg_hours = '0.00'; 
		}
					//Rounding Code
					var rg_hours_time = rg_hours.split(".");
					if(rg_hours_time[1] =='' || rg_hours_time[1] == undefined){
						rg_hours_time[1]=0;
					}
					var rg_hours_mint = '0.'+rg_hours_time[1];
					rg_hours_min = getRoundOffMinutes_time(rg_hours_mint, time_increment);
					rg_hours_final = parseFloat(rg_hours_time[0]) + parseFloat(rg_hours_min);
					//Code Ends
					
		if (rg_hours_final != "") {
			
			if(mode=='edit')
			{
				row_total = 0.00;
				if($(this).hasClass("rowRegularHours"))
					$.each(rowid_hours_for_each_list, function(row_id_key, row_hour) 
					{
						row_id_key = parseInt(row_id_key);
						if(reg_id == row_id_key )
						{
							changed_hours_val_at_current_rowid	= parseFloat(getTotalHoursForADay(reg_id));
						//Rounding Code
						changed_hours_val_at_current_rowid = changed_hours_val_at_current_rowid.toFixed(2);
							var chvacr_time = changed_hours_val_at_current_rowid.split(".");
							if(chvacr_time[1] =='' || chvacr_time[1] == undefined){
								chvacr_time[1]=0;
							}
						var chvacr_mint = '0.'+chvacr_time[1];
						chvacr_min = getRoundOffMinutes_time(chvacr_mint, time_increment);
						chvacr_final = parseFloat(chvacr_time[0]) + parseFloat(chvacr_min);
						//Code Ends
						changed_hours_val_at_current_rowid= chvacr_final.toFixed(2);
							if(changed_hours_val_at_current_rowid !=  parseFloat(row_hour)){
								rowid_hours_for_each_list[row_id_key] =  changed_hours_val_at_current_rowid;
								row_hour = changed_hours_val_at_current_rowid;
							}
						}
					
						
						row_total = parseFloat(row_total) + parseFloat(row_hour);
			
						if (row_total >= reg_max_hours) {
							
							reg_max_rowid	= row_id_key;
							$("#reg_hours_max_row_id").val(reg_max_rowid);
							overtime_hours = parseFloat(row_total) - parseFloat(reg_max_hours);
							$("#ovt_hrs_in_reg_max_row_id").val(overtime_hours);
							return false;
						}else{

							$("#reg_hours_max_row_id").val("0");
							$("#ovt_hrs_in_reg_max_row_id").val("0");
						}
					});
				
			}else
			{
				regular_total	= parseFloat(regular_total) + parseFloat(rg_hours_final);
				if (regular_total >= reg_max_hours) {
					reg_max_rowid	= reg_id;
					return false;
				}

			}
		}
	});

	return reg_max_rowid;
}



function getRowIdForMaxOvertimeHours(ovt_max_hours,ovt_hours_in_regmaxid) {

	var ovt_max_rowid	= -1;
	var overtime_total	= 0.0;
	var ovt_maxid_exists = 0;
	
	var reg_max_rowid = $("#reg_hours_max_row_id").val();
	
		if(mode=='edit')
		{
			overtime_total = parseFloat(ovt_hours_in_regmaxid);
			
			$.each(rowid_hours_for_each_list, function(row_id_key, row_hour) {
				row_id_key = parseInt(row_id_key);
				
				if(reg_max_rowid!=0 && (row_id_key > reg_max_rowid))
				{
					overtime_total =  parseFloat(overtime_total) + parseFloat(row_hour);
					
					if (overtime_total >= ovt_max_hours) {
						
						ovt_max_rowid	= row_id_key;
						$("#ovt_hours_max_row_id").val(ovt_max_rowid);
						return false;
						
					}
						
					
				}
				else
				{
					$("#ovt_hours_max_row_id").val('0');
				}
			
			});
		}
		else{
				$(".rowOverTimeHours").each(function() {

					att_id		= $(this).attr("id");
					ovt_id		= parseInt(att_id.split("_").pop(-1));
					ot_hours	= $("#daily_rate_1_" + ovt_id).val();

					if (ot_hours != "") {
						
						
						overtime_total	= parseFloat(overtime_total) + parseFloat(ot_hours);
						if (overtime_total >= ovt_max_hours) {
							ovt_max_rowid	= ovt_id;
							return false;
						}
					}
				});
		}
	return ovt_max_rowid;
} 


function getMaxOvertimeHoursTotal(rowid){
	
	var reg_max_rowid = parseInt($("#reg_hours_max_row_id").val());
	var ovt_max_row_id = parseInt($("#ovt_hours_max_row_id").val());
	var ovt_hours_total = 0.00;

		
		$.each(rowid_hours_for_each_list, function(row_id_key, row_hour) {
			
				row_id_key = parseInt(row_id_key);
				
				if(row_id_key > reg_max_rowid && row_id_key <= rowid && row_id_key <= ovt_max_row_id)
				{
					ovt_hours_total =  parseFloat(ovt_hours_total) + parseFloat(row_hour);
				}
			
			});
	
		
	return ovt_hours_total
}

function getRegularHoursTotal() {

	var total_reghours	= 0.00;

	$(".rowRegularHours").each(function() {

		att_id	= $(this).attr("id");
		reg_id	= parseInt(att_id.split("_").pop(-1));
		reg_hr	= $("#daily_rate_0_" + reg_id).val();

		if (reg_hr != "") {

			total_reghours	= parseFloat(total_reghours) + parseFloat(reg_hr);
		}
	});

	return total_reghours;
}

function getOverTimeHoursTotal() {

	var total_ovthours	= 0.00;

	$(".rowOverTimeHours").each(function() {

		atr_id	= $(this).attr("id");
		ovt_id	= parseInt(atr_id.split("_").pop(-1));
		ovt_hr	= $("#daily_rate_1_" + ovt_id).val();

		if (ovt_hr != "") {

			total_ovthours	= parseFloat(total_ovthours) + parseFloat(ovt_hr);
		}
	});

	return total_ovthours;
}

function checkDuplicateDates() {

	var selwk_dates	= [];
	var duplt_dates	= [];

	var dup_row_ids	= [];
	var all_row_ids	= [];

	$(".rowRegularHours").each(function() {

		atr_id	= $(this).attr("id");
		row_id	= parseInt(atr_id.split("_").pop(-1));

		all_row_ids.push(row_id);
	});

	$(".rowRegularHours").each(function() {

		atr_id	= $(this).attr("id");
		row_id	= parseInt(atr_id.split("_").pop(-1));
		sel_dt	= $("#daily_dates_" + row_id).val();

		if (jQuery.inArray(sel_dt, selwk_dates) == -1) {

			selwk_dates.push(sel_dt);

		} else {

			if (jQuery.inArray(sel_dt, duplt_dates) == -1)
			duplt_dates.push(sel_dt);
		}
	});

	for (i = 0; i < all_row_ids.length; i++) {

		row_date	= $("#daily_dates_" + all_row_ids[i]).val();

		for (j = 0; j < duplt_dates.length; j++) {

			if (row_date == duplt_dates[j]) {

				dup_row_ids.push(all_row_ids[i]);
			}
		}
	}

	for (m = 0; m < duplt_dates.length; m++) {

		tot_hours	= 0.0;
		dup_date	= duplt_dates[m];

		for (k = 0; k < dup_row_ids.length; k++) {

			row_date	= $("#daily_dates_" + dup_row_ids[k]).val();

			if (dup_date == row_date) {

				sum_hours	= getSumOfHoursForADay(dup_row_ids[k]);
				tot_hours	= parseFloat(tot_hours) + parseFloat(sum_hours);
			}
		}

		if (tot_hours > parseFloat(wk_maxlimithoursaday)) {

			return true;
		}
	}

	return false;
}

function getSumOfHoursForADay(row_id) {

	var sum_hours	= 0.0;
	var reg_hours	= $("#daily_rate_0_" + row_id).val();
	var ovt_hours	= $("#daily_rate_1_" + row_id).val();
	var dbt_hours	= $("#daily_rate_2_" + row_id).val();

	if (reg_hours != "") {

		sum_hours	= sum_hours + parseFloat(reg_hours);
	}

	if (ovt_hours != "") {

		sum_hours	= sum_hours + parseFloat(ovt_hours);
	}

	if (dbt_hours != "") {

		sum_hours	= sum_hours + parseFloat(dbt_hours);
	}

	sum_hours	= parseFloat(sum_hours);

	return sum_hours;
}
function getTitoTimesheetDateRangeBeforeSubmit(){

	var moduleName = document.getElementById("module").value;
	
	if(moduleName == 'MyProfile'){
		var empUsernames = document.getElementById("empnames_myprofile").value;
	}else{
		var emp = document.getElementById("empnames");
		var empUsernames = emp.options[emp.selectedIndex].value;
	}
	

	var checking_from = document.getElementById("checking_from").value;
	var checking_to = document.getElementById("checking_to").value;
	var getdates = document.getElementById("getdates").value;
	var lockdownflag = document.getElementById("lockdown_flag").value;
	
	var content = "rtype=getTimesheetStatus&multiple=NO&moduleName="+moduleName+"&dateRangeFilled=No&checking_from="+checking_from+"&checking_to="+checking_to+"&empUsernames="+empUsernames+"&getdates="+getdates+"&onloadCheck=onload"+"&lockdown_flag="+lockdownflag;
	$.ajax({
	    url: '/BSOS/Include/getAsgn.php',
	    data: content,
	    method: 'POST',
	    success: function (response)
	    {	
	    	if(response !=0){
	    		var dateRange = response.split('|');
	    		alert("Timesheet for the below dates already exists.\n"+dateRange[0]+"\nClick OK to go back and change the dates and submit time sheet for different dates.");
	    		$('#tcalico_0').click();
	    	}
		 
	    }
	});
}

//Function used to validate the all assignments selected belongs to california/ca
function chk_state_of_asgn(){
	
	seventh_day_rule_flag = true;
	$("select.daily_assignemnt").each(function() {
		//console.log('chk state fun called.');
		var selected = $(this).find('option:selected');
		var opt_val = selected.val();
		if(opt_val.indexOf("(earn)")>-1){
			return true;
		}
		
		var location_state = selected.data('location_state');
		//console.log(location_state);
		if (jQuery.inArray(location_state, seventh_day_rule_states)=='-1') {
			seventh_day_rule_flag = false;
			//console.log("other state is selected");
		}
	});
	return seventh_day_rule_flag;
}