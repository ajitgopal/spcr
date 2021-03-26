<?php
class CustomGridFunctions
{   
    var $thcols = '';
    var $mod_details;
    //////////////////////////////////////////////////////////////////
    //////// FUNCTION TO GET THE MODULE ID FROM MASTER TABLE /////////
    //////////////////////////////////////////////////////////////////
    function getModuleid($modulename)
    {
        global $db;
        $sel_module_id = "select module_id from udf_form_modules where module_desc = '".$modulename."'";
        $res_module_id = mysql_query($sel_module_id , $db) or die(mysql_error());
        $rec_module_id = mysql_fetch_array($res_module_id);     
        return $rec_module_id['module_id'];
    }
    
    //////////////////////////////////////////////////////////////////
    //////// FUNCTION TO GET THE MODULE ID FROM MASTER TABLE /////////
    //////////////////////////////////////////////////////////////////
    function getModuleid_new($modulename)
    {
        global $db;
        $sel_module_id = "select module_id,primary_table from udf_form_modules where module_desc = '".$modulename."'";
        $res_module_id = mysql_query($sel_module_id, $db) or die(mysql_error());
        $rec_module_id = mysql_fetch_array($res_module_id); 
        
        return($rec_module_id);
    }
    
    //////////////////////////////////////////////////////
    //////// FUNCTION TO GET USER GRID DETAILS  //////////
    //////////////////////////////////////////////////////
    function getGridName($modulename,$userid,$cvsid=0)
    {
        global $db;
        $module_id = $this->getModuleId($modulename);
        
        $sel_grid_name = "select id as grid_name_id,grid_name from udv_grids where custom_form_modules_id = '".$module_id."' and cuser = ".$userid;
        if(!empty($cvsid) && ($cvsid!=0))
        {
            $sel_grid_name = "select id as grid_name_id,search_name as grid_name from udv_grids_savedsearch where id=".$cvsid." AND custom_form_modules_id = '".$module_id."' and cuser = ".$userid;
        }
        $res_grid_name = mysql_query($sel_grid_name, $db) or die(mysql_error());
        $rec_grid_name = mysql_fetch_assoc($res_grid_name);     
        return $rec_grid_name;
    }
    
    //////////////////////////////////////////////////////////////////
    //////// FUNCTION TO UDPATED OR INSERT USER GRID DETAILS /////////
    //////////////////////////////////////////////////////////////////
    function updateGridName($modulename, $grid_name_id, $gridtitle, $userid, $cvsid=0)
    {   
        global $db;
        $module_id = $this->getModuleId($modulename);
        if(!empty($cvsid) && ($cvsid!=0))
        {
            $grid_details_query = "update udv_grids_savedsearch set custom_form_modules_id = ".$module_id.", ";
            $grid_details_query .= "muser = ".$userid.", mdate = NOW()";
            $grid_details_query .= "where id = ".$cvsid;
            mysql_query($grid_details_query, $db);
            return 0;
        }else
        {
            if($grid_name_id != 0)
            {
                $grid_details_query = "update udv_grids set custom_form_modules_id = ".$module_id.", ";
                $grid_details_query .= "grid_name = '".$gridtitle."', muser = ".$userid.", mdate = NOW() ";
                $grid_details_query .= "where id = ".$grid_name_id;
                mysql_query($grid_details_query, $db);
                return $grid_name_id;
            }
            else
            {
                $grid_details_query = "insert udv_grids set custom_form_modules_id = ".$module_id.", ";
                $grid_details_query .= "grid_name = '".$gridtitle."', muser = ".$userid.", mdate = NOW(), ";
                $grid_details_query .= "cuser = '".$userid."', cdate = NOW()";
                mysql_query($grid_details_query, $db);
                return mysql_insert_id();
            }
        }
    }
    
    //////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO GET AVAILABLE COLUMNS FROM MASTER TABLE /////////
    //////////////////////////////////////////////////////////////////////  
    function availableCols($modulename, $userid, $cvsid=0)
    {
        global $db;
        $module_id = $this->getModuleId($modulename);
        
        
        //$sel_selectedcols = "select custom_grid_column_id from udv_grids where custom_grid_column_id in (select id from udv_grid_columns where app_modules_id = ".$module_id.")";
        $sel_selectedcols = "select cgcols.id ";
        if(!empty($cvsid) && $cvsid!=0)
        {
            $sel_selectedcols .= "from udv_grids_savedsearch cg ";
            $sel_selectedcols .= "LEFT JOIN udv_grids_usercolumns cgucols ON cgucols.savedsearch_id = cg.id AND cgucols.custom_grid_id=0 ";
        }else
        {
            $sel_selectedcols .= "from udv_grids cg ";
            $sel_selectedcols .= "LEFT JOIN udv_grids_usercolumns cgucols ON cgucols.custom_grid_id = cg.id ";
        }

        $sel_selectedcols .= "LEFT JOIN udv_grid_columns cgcols ON cgcols.id = cgucols.custom_grid_column_id ";
        $sel_selectedcols .= "where cg.custom_form_modules_id = ".$module_id." and cg.cuser = ".$userid;
        if(!empty($cvsid) && $cvsid!=0)
        {
            $sel_selectedcols .= " AND cg.id = ".$cvsid."";
        }
        $res_selectedcols = mysql_query($sel_selectedcols, $db);
        
        if(mysql_num_rows($res_selectedcols) <= 0)
        {
        
            $sel_selectedcols = "SELECT cgcols.id ";
            $sel_selectedcols .= "FROM udv_grid_columns cgcols ";
            $sel_selectedcols .= "LEFT JOIN udf_form_modules cfm ON cfm.module_id = cgcols.custom_form_modules_id ";
            $sel_selectedcols .= "WHERE cgcols.custom_form_modules_id =".$module_id." AND cgcols.defaultflag = 1 ";
            $sel_selectedcols .= "ORDER BY cgcols.defaultorder ASC";
            
            $res_selectedcols = mysql_query($sel_selectedcols, $db);
        }
        
        $rec_selectedcols = mysql_fetch_array($res_selectedcols);
        
        
        
        if($rec_selectedcols['id'] == NULL)
            $sel_selectedcols =  "''";
        
        
        $sel_avalcols = "select id, column_name, ref_table from udv_grid_columns where custom_form_modules_id = ".$module_id." and id NOT IN (".$sel_selectedcols.") and allow_on_grid = 1 AND udfstatus = 1 order by column_name"; 
                
        $res_avalcols = mysql_query($sel_avalcols, $db);
        
        while($rec_avalcols = mysql_fetch_array($res_avalcols))
        {           
            $udfpas = strpos($rec_avalcols['ref_table'], "udf_");
            
            if($udfpas === false)
                $optstyle = '';
            else
                $optstyle = 'style="background-color:#d1d4d9;"';
            
            $optval = '<option value="'.$rec_avalcols['id'].'" '.$optstyle.'>'.$rec_avalcols['column_name'].'</option>';
            echo $optval;
        }
            
    }
    
    //////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO GET SELECTED COLUMNS FOR CUSTOM GRID  ///////////
    //////////////////////////////////////////////////////////////////////
    function selectedColsQuery($modulename,$userid,$cvsid=0)
    {
        global $db;
        $module_id = $this->getModuleId($modulename);
        
        $sel_selected_grid_cols = "select cgcols.id, cgcols.custom_form_modules_id, cgcols.column_name, cgcols.db_col_name, cgcols.ref_table, cgcols.ref_column_name, cgcols.ref_target_column_name, cfm.primary_table, cgcols.grid_logic, cgcols.db_col_type, cgcols.col_alias, cgcols.search_logic, cgcols.grid_logic_with_leftjoin, cgcols.allow_leftjoin, cgucols.column_order ";
        if(!empty($cvsid) && $cvsid!=0)
        {
            $sel_selected_grid_cols .= "from udv_grids_savedsearch cg ";
            $sel_selected_grid_cols .= "LEFT JOIN udv_grids_usercolumns cgucols ON cgucols.savedsearch_id = cg.id AND cgucols.custom_grid_id=0 ";
        }else
        {
            $sel_selected_grid_cols .= "from udv_grids cg ";
            $sel_selected_grid_cols .= "LEFT JOIN udv_grids_usercolumns cgucols ON cgucols.custom_grid_id = cg.id ";
        }
        
        $sel_selected_grid_cols .= "LEFT JOIN udv_grid_columns cgcols ON cgcols.id = cgucols.custom_grid_column_id ";
        $sel_selected_grid_cols .= "LEFT JOIN udf_form_modules cfm ON cfm.module_id = cgcols.custom_form_modules_id ";
        $sel_selected_grid_cols .= "where cg.custom_form_modules_id = ".$module_id." and cg.cuser = ".$userid." and  cgcols.udfstatus = 1 and cgcols.allow_on_grid = 1";
        if(!empty($cvsid) && $cvsid!=0)
        {
            $sel_selected_grid_cols .= " AND cg.id = ".$cvsid."";
        }
        $sel_selected_grid_cols .= " order by cgucols.column_order";
        

        $res_selected_grid_cols = mysql_query($sel_selected_grid_cols, $db);
        
        if(mysql_num_rows($res_selected_grid_cols) <= 0)
        {
            $sel_selected_grid_cols = "SELECT cgcols.id, cgcols.custom_form_modules_id, cgcols.column_name, cgcols.db_col_name, cgcols.ref_table, cgcols.ref_column_name, cgcols.ref_target_column_name, cfm.primary_table, cgcols.grid_logic, cgcols.db_col_type, cgcols.col_alias, cgcols.search_logic, cgcols.grid_logic_with_leftjoin, cgcols.allow_leftjoin, cgcols.defaultorder as column_order ";
            $sel_selected_grid_cols .= "FROM udv_grid_columns cgcols ";
            $sel_selected_grid_cols .= "LEFT JOIN udf_form_modules cfm ON cfm.module_id = cgcols.custom_form_modules_id ";
            $sel_selected_grid_cols .= "WHERE cgcols.`custom_form_modules_id` =".$module_id." AND cgcols.`defaultflag` = 1 AND cgcols.udfstatus = 1  and cgcols.allow_on_grid = 1 ";
            $sel_selected_grid_cols .= "ORDER BY cgcols.defaultorder ASC";
            
            $res_selected_grid_cols = mysql_query($sel_selected_grid_cols, $db);
        }
        return $res_selected_grid_cols;
    }
    
    
    //////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO GET SELECTED COLUMNS FROM MASTER TABLE //////////
    //////////////////////////////////////////////////////////////////////
    function selectedCols($modulename,$userid,$cvsid=0)
    {       
        global $db;
        $res_selectedcols = $this->selectedColsQuery($modulename,$userid,$cvsid);       
        
        while($rec_selectedcols = mysql_fetch_array($res_selectedcols))
        {           
            $udfpas = strpos($rec_selectedcols['ref_table'], "udf_");
            if($udfpas === false)
                $optstyle = '';
            else
                $optstyle = 'style="background-color:#d1d4d9;"';
            
            $optval = '<option value="'.$rec_selectedcols['id'].'" '.$optstyle.' >'.$rec_selectedcols['column_name'].'</option>';
            
            echo $optval;
            
            //echo '<option value="'.$rec_selectedcols['id'].'">'.$rec_selectedcols['column_name'].'</option>';
        }
    }
    
    
    /////////////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO DELETE EXISTING COLUMNS WHICH ARE NOT SELECTED /////////
    /////////////////////////////////////////////////////////////////////////////   
    function deleteCustomCols($columnids, $userid, $grid_name_id, $cvsid=0)
    {   
        global $db;
        if(!empty($columnids))
            $qcols = $columnids;
        else
            $qcols = "''";
        
        $del_udv_grids = "delete from udv_grids_usercolumns where custom_grid_column_id NOT IN (".$qcols.") and ";
        $del_udv_grids .= "savedsearch_id = ".$cvsid." and ";
        $del_udv_grids .= "c_user_id = ".$userid." and custom_grid_id = ".$grid_name_id;
        
        mysql_query($del_udv_grids, $db);
    }   
    
    //////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO INSERT OR UPDATED SELECTED COLUMNS //////////////
    //////////////////////////////////////////////////////////////////////
    function insertCustomCols($columnid, $order_var, $userid, $grid_name_id, $cvsid=0)
    {
        global $db;
        $sel_udv_grids = "select id from udv_grids_usercolumns where ";
        $sel_udv_grids .= "custom_grid_column_id = ".$columnid." and c_user_id = ".$userid." and ";
        $sel_udv_grids .= "savedsearch_id = ".$cvsid." and ";
        $sel_udv_grids .= "custom_grid_id = ".$grid_name_id;    
        
            
        $res_udv_grids = mysql_query($sel_udv_grids, $db);
        
        if(mysql_num_rows($res_udv_grids) <= 0)
        {       
            $ins_udv_grids = "insert into udv_grids_usercolumns  set ";
            $ins_udv_grids .= "custom_grid_id = ".$grid_name_id.", custom_grid_column_id = ".$columnid.", ";
            $ins_udv_grids .= "column_order = ".$order_var.", c_user_id = ".$userid.", m_user_id = ".$userid.", ";
            $ins_udv_grids .= "savedsearch_id = ".$cvsid.", ";
            $ins_udv_grids .= "c_date = NOW(), m_date = NOW() ";
            
            mysql_query($ins_udv_grids, $db);
        }
        else
        {
            $rec_udv_grids = mysql_fetch_array($res_udv_grids);
        
            $upd_udv_grids = "UPDATE udv_grids_usercolumns  set ";
            $upd_udv_grids .= "custom_grid_id = ".$grid_name_id.", custom_grid_column_id = ".$columnid.", ";
            $upd_udv_grids .= "column_order = ".$order_var.", m_user_id = ".$userid.", ";
            $ins_udv_grids .= "savedsearch_id = ".$cvsid.", ";
            $upd_udv_grids .= "m_date = NOW() ";
            $upd_udv_grids .= "where id = ".$rec_udv_grids['id'];
                        
            mysql_query($upd_udv_grids, $db);
        }       
    }
    
    //////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO BUILD GRID WITH SELECTED COLUMNS /////////////
    //////////////////////////////////////////////////////////////////////
    function buildCustomGridHeaders($modulename, $userid, $cvsid=0)
    {
        global $db;
        $res_custom_grid_cols_headers = $this->selectedColsQuery($modulename, $userid, $cvsid);
        
        while($rec_custom_grid_cols_headers = mysql_fetch_array($res_custom_grid_cols_headers))     
            $this->thcols .= "<th>".$rec_custom_grid_cols_headers['column_name']."</th>";
        
        echo $this->thcols;
    }

    //////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO EXPORT FROM GRID  VALUES/////////////
    //////////////////////////////////////////////////////////////////////
    function buildExportGridHeaders($modulename, $userid, $cvsid=0)
    {
        global $db;
        $res_custom_grid_cols_headers = $this->selectedColsQuery($modulename, $userid, $cvsid);
        
        while($rec_custom_grid_cols_headers = mysql_fetch_array($res_custom_grid_cols_headers))     
            $this->thcols .= $rec_custom_grid_cols_headers['column_name'].",";
        
        echo $this->thcols;
    }
    
    ////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO GET UDF LABLE VLUES for EXPORT/////////////
    ///////////////////////////////////////////////////////////////////
    function getUdflablesforExport($modulename)
    {
        global $db;
        $moduleid = $this->getModuleid($modulename);
        $udf_lables = '';
        $sel_udf_lables = "SELECT * FROM udf_form_details t1";
        $sel_udf_lables .= " LEFT JOIN udf_form_details_order t2 ON t2.custom_form_details_id = t1.id ";
        $sel_udf_lables .= "WHERE module = ".$moduleid." and status = 'Active' ORDER BY t2.ele_order ASC";
        
        $res_udf_lables = mysql_query($sel_udf_lables, $db);
        
        while($rec_custom_grid_lables = mysql_fetch_array($res_udf_lables))
        {
            $udf_lables  .=  urldecode($rec_custom_grid_lables['element_name']).",";
        }
        
        return $udf_lables;
    }
    
    ////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO GET UDF COLUMNS NAMES for EXPORT/////////////
    ///////////////////////////////////////////////////////////////////
    function getUdfColsforExport($modulename)
    {
        global $db;
        $moduleid = $this->getModuleid($modulename);
        $udf_lables = '';
        $sel_udf_lables = "SELECT * FROM udf_form_details t1";
        $sel_udf_lables .= " LEFT JOIN udf_form_details_order t2 ON t2.custom_form_details_id = t1.id ";
        $sel_udf_lables .= "WHERE module = ".$moduleid." and status = 'Active' ORDER BY t2.ele_order ASC";
        
        $res_udf_lables = mysql_query($sel_udf_lables, $db);
        
        while($rec_custom_grid_lables = mysql_fetch_array($res_udf_lables))
        {
            $udf_lables  .=  urldecode($rec_custom_grid_lables['element_lable']).",";
        }
        
        return $udf_lables;
    }
    
    ///////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO GET UDF Contents VLUES for EXPORT/////////////
    //////////////////////////////////////////////////////////////////////
    function getUdfContents($modulename, $recordid)
    {
        global $db;
        $udf_values_arr = array();
        $moduleid = $this->getModuleid($modulename);
        
        switch($modulename)
        {
            case "contacts":
                $fields = trim($this->getUdfColsforExport('contacts'),",");
                $sel_udf_values = "SELECT ".$fields." FROM udf_form_details_contact_values WHERE rec_id = ".$recordid;                  
                $res_udf_values = mysql_query($sel_udf_values, $db);
                $res_udf_values_count = mysql_num_rows($res_udf_values);
                if($res_udf_values_count > 0)
                {
                    $rec_udf_values = mysql_fetch_row($res_udf_values);
                    for($i = 0; $i<count($rec_udf_values); $i++)
                    {
                        $udf_values_arr[] = '"'.html_tls_specialchars(stripslashes(trim($rec_udf_values[$i])),ENT_QUOTES).'"';
                    }
                    $udf_values = implode(",", $udf_values_arr);                    
                }
                else
                {
                    $udf_lable_count = $this->getUdflablesforExport($modulename);
                    $rec_udf_values = count(explode(",", trim($udf_lable_count,',')));                  
                    $udf_values = str_repeat(",", $rec_udf_values);                     
                }
                $udf_count = $this->getUdflablesforExport($modulename);
                break;
            case "companies":
                $fields = trim($this->getUdfColsforExport('companies'),",");
                $sel_udf_values = "SELECT ".$fields." FROM udf_form_details_companie_values WHERE rec_id = ".$recordid;                 
                $res_udf_values = mysql_query($sel_udf_values, $db);
                $res_udf_values_count = mysql_num_rows($res_udf_values);
                if($res_udf_values_count > 0)
                {
                    $rec_udf_values = mysql_fetch_row($res_udf_values);
                    for($i = 0; $i<count($rec_udf_values); $i++)
                    {
                        $udf_values_arr[] = '"'.html_tls_specialchars(stripslashes(trim($rec_udf_values[$i])),ENT_QUOTES).'"';
                    }
                    $udf_values = implode(",", $udf_values_arr);
                }
                else
                {
                    $udf_lable_count = $this->getUdflablesforExport($modulename);
                    $rec_udf_values = count(explode(",", trim($udf_lable_count,',')));                  
                    $udf_values = str_repeat(",", $rec_udf_values);                     
                }
                $udf_count = $this->getUdflablesforExport($modulename);
                break;
            case "candidates":
                $fields = trim($this->getUdfColsforExport('candidates'),",");
                $sel_udf_values = "SELECT ".$fields." FROM udf_form_details_candidate_values WHERE rec_id = ".$recordid;                    
                $res_udf_values = mysql_query($sel_udf_values, $db);
                $res_udf_values_count = mysql_num_rows($res_udf_values);
                if($res_udf_values_count > 0)
                {
                    $rec_udf_values = mysql_fetch_row($res_udf_values);
                    for($i = 0; $i<count($rec_udf_values); $i++)
                    {
                        $udf_values_arr[] = '"'.html_tls_specialchars(stripslashes(trim($rec_udf_values[$i])),ENT_QUOTES).'"';
                    }
                    $udf_values = implode(",", $udf_values_arr);
                }
                else
                {
                    $udf_lable_count = $this->getUdflablesforExport($modulename);
                    $rec_udf_values = count(explode(",", trim($udf_lable_count,',')));                  
                    $udf_values = str_repeat(",", $rec_udf_values);                     
                }
                $udf_count = $this->getUdflablesforExport($modulename);
                break;
            case "job orders":
                $fields = trim($this->getUdfColsforExport('job orders'),",");
                $sel_udf_values = "SELECT ".$fields." FROM udf_form_details_joborder_values WHERE rec_id = ".$recordid;                 
                $res_udf_values = mysql_query($sel_udf_values, $db);
                
                $res_udf_values_count = mysql_num_rows($res_udf_values);
                if($res_udf_values_count > 0 )
                {
                    $rec_udf_values = mysql_fetch_row($res_udf_values);
                    for($i = 0; $i<count($rec_udf_values); $i++)
                    {
                        $udf_values_arr[] = '"'.html_tls_specialchars(stripslashes(trim($rec_udf_values[$i])),ENT_QUOTES).'"';
                    }
                    $udf_values = implode(",", $udf_values_arr);
                }
                else
                {
                    $udf_lable_count = $this->getUdflablesforExport($modulename);
                    $rec_udf_values = count(explode(",", trim($udf_lable_count,',')));                  
                    $udf_values = str_repeat(",", $rec_udf_values);                     
                }
                $udf_count = $this->getUdflablesforExport($modulename);
                break;
            default:
                $udf_values = '';
                break;
        }
        
        //$udf_values = $rec_udf_values."<br>".$modulename."----".$recordid;
        if($udf_count > 1)
            $udf_values = substr($udf_values,0,-1);
        
        return $udf_values;
    }
    
    //////////////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO BUILD ACTUALL ARRAY FOR SELECTED COLUMNS /////////////
    //////////////////////////////////////////////////////////////////////////////
    function searchQueryStrings($modulename, $userid, $cvsid=0)
    {
        global $user_timezone,$db;
    
        $index = 0;
        $mod_details = $this->getModuleid_new($modulename);     
        $res_selected_cols = $this->selectedColsQuery($modulename,$userid,$cvsid);  
        $join_alais = 0;
        $array_table = array();
                if($modulename=='candidates'){
                    while($rec_selected_cols = mysql_fetch_assoc($res_selected_cols))   
                    {
                        if(!empty($rec_selected_cols['ref_table']))
                        {                   
                            if(!empty($rec_selected_cols['search_logic']))
                            {
                                $rec_selected_cols['search_logic'] = str_replace("TZ@TZ@TZ", $user_timezone[0], $rec_selected_cols['search_logic']);
                                if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                {
                                    $join_alais = $join_alais+1;
                                    $searchArray[$index++] = str_replace('!!!!',"t".$join_alais,$rec_selected_cols['search_logic']);
                                    $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                }
                                else
                                {
                                    $searchArray[$index++] = str_replace('!!!!',$array_table[$rec_selected_cols['ref_table']],$rec_selected_cols['search_logic']);                              
                                }    
                            }
                            elseif(!empty($rec_selected_cols['db_col_type']))
                            {
                                if($rec_selected_cols['db_col_type'] == 'date')
                                {
                                    if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                    {
                                        $join_alais = $join_alais+1;
                                        $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                    }
                                }
                                if($rec_selected_cols['db_col_type'] == 'regular_date')
                                {
                                    if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                    {
                                        $join_alais = $join_alais+1;
                                        $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                    }
                                }
                                $searchArray[$index++] = '';
                            }
                            else
                            {
                                if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                {
                                        $join_alais = $join_alais+1;
                                        $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                }
                                $searchArray[$index++] = '';
                            }
                        }
                        else
                        {
                            if(!empty($rec_selected_cols['search_logic']))
                            {
                                $rec_selected_cols['search_logic'] = str_replace("TZ@TZ@TZ", $user_timezone[0], $rec_selected_cols['search_logic']);
                                $searchArray[$index++] = str_replace('!!!!',$array_table[$rec_selected_cols['ref_table']],$rec_selected_cols['search_logic']);                              

                            }
                            else
                            {
                                $searchArray[$index++] = '';
                            }                           
                        }
                    }
                }
                else
                {
                    while($rec_selected_cols = mysql_fetch_assoc($res_selected_cols))   
                    {
            if(!empty($rec_selected_cols['search_logic']))
            {
                $rec_selected_cols['search_logic'] = str_replace("TZ@TZ@TZ", $user_timezone[0], $rec_selected_cols['search_logic']);
                if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                {
                
                $join_alais = $join_alais+1;
                $searchArray[$index++] = str_replace('!!!!',"t".$join_alais,$rec_selected_cols['search_logic']);
                $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                }
                else
                {
                $searchArray[$index++] = str_replace('!!!!',$array_table[$rec_selected_cols['ref_table']],$rec_selected_cols['search_logic']);                              
                }
            }
            else
            {
                $searchArray[$index++] = '';
                
            }
            }
                }
                
        return $searchArray;
    }
        
    
    //////////////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO BUILD ACTUALL ARRAY FOR SELECTED COLUMNS /////////////
    //////////////////////////////////////////////////////////////////////////////
    function asFields($modulename, $userid, $cvsid=0)
    {
        global $db;
        $index = 0;
        $mod_details = $this->getModuleid_new($modulename);     
        $res_selected_cols = $this->selectedColsQuery($modulename,$userid,$cvsid);
        $join_alais = 0;
        $asFields[$index++] = '';
        $array_table = array(); 
        
        while($rec_selected_cols = mysql_fetch_assoc($res_selected_cols))   
        {
            if(!empty($rec_selected_cols['ref_table']))
            {
                if($modulename='candidates'){
                                
                                if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                {

                                    $join_alais = $join_alais+1;
                                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                                    {
                                            if(!empty($rec_selected_cols['grid_logic']))
                                                    $asFields[$index++] = "DATE_FORMAT(".str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']).", '%m/%d/%Y')";
                                    
                                    }elseif($rec_selected_cols['ref_target_column_name'] == 'availsdate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Available Starting' && $rec_selected_cols['ref_table']=='candidate_prof'){
                                        
                                        if(!empty($rec_selected_cols['grid_logic'])){ 
                                            $asFields[$index++] = str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']);
                                        }else{
                                            $asFields[$index++] = "t".$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                                        }
                                    
                                    }else if($rec_selected_cols['ref_target_column_name'] == 'availedate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Not Available After'  && $rec_selected_cols['ref_table']=='candidate_prof'){

                                        if(!empty($rec_selected_cols['grid_logic'])){ 
                                            $asFields[$index++] = str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']);
                                        }else{
                                            $asFields[$index++] = "t".$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                                        } 
                                    }
                                    else
                                    {
                                            $asFields[$index++] = "t".$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                                    }
                                    
                                    $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                }
                                else
                                {
                                    if($rec_selected_cols['db_col_type'] == 'udf_date'){
                                            $asFields[$index++] = "DATE_FORMAT(".str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']).", '%m/%d/%Y')";
                                    
                                    }elseif($rec_selected_cols['ref_target_column_name'] == 'availsdate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Available Starting' && $rec_selected_cols['ref_table']=='candidate_prof'){
                                        
                                        if(!empty($rec_selected_cols['grid_logic'])){ 
                                            $asFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                        }else{
                                            $asFields[$index++] = $array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'];
                                        }
                                    
                                    }else if($rec_selected_cols['ref_target_column_name'] == 'availedate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Not Available After'  && $rec_selected_cols['ref_table']=='candidate_prof'){

                                        if(!empty($rec_selected_cols['grid_logic'])){ 
                                            $asFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                        }else{
                                            $asFields[$index++] = $array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'];
                                        } 
                                    }else{
                    $asFields[$index++] = $array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'];
                                    }
                                }
                            }
                            else{
                                if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                {

                                    $join_alais = $join_alais+1;
                                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                                    {
                                            if(!empty($rec_selected_cols['grid_logic']))
                                                    $asFields[$index++] = "DATE_FORMAT(".str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']).", '%m/%d/%Y')";
                                    }
                                    else
                                    {
                                            $asFields[$index++] = "t".$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                                    }
                                    $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                }
                                else
                                {
                                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                                            $asFields[$index++] = "DATE_FORMAT(".str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']).", '%m/%d/%Y')";
                                    else
                    $asFields[$index++] = $array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'];                               
                                }
                            }
                        }else
            {
                $asFields[$index++] = $mod_details['primary_table'].".".$rec_selected_cols['db_col_name'];
                
            }
        }
        return $asFields;
    }
    
    //////////////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO BUILD ACTUALL ARRAY FOR Export  /////////////
    //////////////////////////////////////////////////////////////////////////////
    function asFieldsforExport($modulename, $userid, $cvsid=0)
    {
        global $db;
        $index = 0;
        $mod_details = $this->getModuleid_new($modulename);     
        $res_selected_cols = $this->selectedColsQuery($modulename,$userid,$cvsid);  
        $join_alais = 1;
        $asFields[$index++] = '';
        
        while($rec_selected_cols = mysql_fetch_assoc($res_selected_cols))   
        {               
            if(!empty($rec_selected_cols['ref_table']))
            {   
                $asFields[$index++] = $rec_selected_cols['ref_table'].".".$rec_selected_cols['ref_target_column_name']; 
                $join_alais++;
            }
            else
            {
                $asFields[$index++] = $mod_details['primary_table'].".".$rec_selected_cols['db_col_name'];
            }
        }
        
        return $asFields;
    }
    
    ///////////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO BUILD SORT ARRAY FOR SELECTED COLUMNS /////////////
    ///////////////////////////////////////////////////////////////////////////
    function ssFields($modulename, $userid, $cvsid=0)
    {
        global $db;
        $index = 0;
        
        $mod_details = $this->getModuleid_new($modulename);     
        $res_selected_cols = $this->selectedColsQuery($modulename,$userid,$cvsid);
        $join_alais = 0;
        $ssFields[$index++] = '';
        $array_table = array();                     
        
        while($rec_selected_cols = mysql_fetch_assoc($res_selected_cols))   
        {               
            if(!empty($rec_selected_cols['ref_table']))
            {
                if($modulename=='candidates'){
                                
                                if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                { 
                                    $join_alais = $join_alais+1;
                                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                                    {
                                            $ssFields[$index++] = str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']);
                                    
                                    }elseif($rec_selected_cols['ref_target_column_name'] == 'availsdate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Available Starting' && $rec_selected_cols['ref_table']=='candidate_prof'){
                                        
                                         $ssFields[$index++] = str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']);
                                    
                                    }else if($rec_selected_cols['ref_target_column_name'] == 'availedate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Not Available After'  && $rec_selected_cols['ref_table']=='candidate_prof'){

                                         $ssFields[$index++] = str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']);
                                    }
                                    else
                                    {   
                                            if(empty($rec_selected_cols['col_alias']))              
                                                    $ssFields[$index++] = "t".$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                                            else
                                                    $ssFields[$index++] = $rec_selected_cols['col_alias'];  
                                    }
                                    $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                }
                                else
                                {
                                    if(empty($rec_selected_cols['col_alias']))
                                    {
                                            if($rec_selected_cols['db_col_type'] == 'udf_date'){
                                                
                                                $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                                    
                                            }elseif($rec_selected_cols['ref_target_column_name'] == 'availsdate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Available Starting' && $rec_selected_cols['ref_table']=='candidate_prof'){
                                        
                                                $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                    
                                            }else if($rec_selected_cols['ref_target_column_name'] == 'availedate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Not Available After'  && $rec_selected_cols['ref_table']=='candidate_prof'){

                                                $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                            }else{
                                                    $ssFields[$index++] = $array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'];
                                            }
                                    }
                                    else
                                    {
                    if($rec_selected_cols['db_col_type'] == 'udf_date'){
                    
                                            $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                        
                                        }elseif($rec_selected_cols['ref_target_column_name'] == 'availsdate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Available Starting' && $rec_selected_cols['ref_table']=='candidate_prof'){
                                        
                                                $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                    
                                        }else if($rec_selected_cols['ref_target_column_name'] == 'availedate' &&  $rec_selected_cols['custom_form_modules_id']==3 && $rec_selected_cols['column_name'] == 'Not Available After'  && $rec_selected_cols['ref_table']=='candidate_prof'){

                                                $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                                        }else
                    {
                                                $ssFields[$index++] = $rec_selected_cols['col_alias'];
                    }
                                    }
                                } 
                                
                            }
                            else{
                            
                                if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                                { 
                                    $join_alais = $join_alais+1;
                                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                                    {
                                            $ssFields[$index++] = str_replace("@^@", "t".$join_alais, $rec_selected_cols['grid_logic']);
                                    }
                                    else
                                    {   
                                            if(empty($rec_selected_cols['col_alias']))              
                                                    $ssFields[$index++] = "t".$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                                            else
                                                    $ssFields[$index++] = $rec_selected_cols['col_alias'];  
                                    }
                                    $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                                }
                                else
                                {
                if(empty($rec_selected_cols['col_alias']))
                {
                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                        $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                    else
                        $ssFields[$index++] = $array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'];
                }
                else
                {
                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                        $ssFields[$index++] = str_replace("@^@", $array_table[$rec_selected_cols['ref_table']], $rec_selected_cols['grid_logic']);
                    else
                    {
                        $ssFields[$index++] = $rec_selected_cols['col_alias'];
                    }
                }
                }
                            
                            }
            }
            else
            {
                if(empty($rec_selected_cols['col_alias']))              
                    $ssFields[$index++] = $mod_details['primary_table'].".".$rec_selected_cols['db_col_name'];
                else
                    $ssFields[$index++] = $rec_selected_cols['col_alias'];          
            }
        }
        return $ssFields;
    }
    
    ///////////////////////////////////////////////////////////////////////
        /////////// FUNCTION TO BUILD QUERY WITH SELECTED COLUMNS /////////////
        ///////////////////////////////////////////////////////////////////////
        function queryBuilder($modulename, $userid, $cvsid=0)
        {
                global $username, $db;
                $mod_details = $this->getModuleid_new($modulename);
                if($modulename == 'job orders')
                        $fields = $mod_details['primary_table'].'.posid,';
                else
                        $fields = $mod_details['primary_table'].'.sno,';
                
                $res_selected_cols = $this->selectedColsQuery($modulename,$userid,$cvsid);      
                $left_joins = '';   
                $join_alais = 0;
                $array_table = array();
                
               while($rec_selected_cols = mysql_fetch_assoc($res_selected_cols))    
                {   
                        if(!empty($rec_selected_cols['ref_table']))
            {                   
                if(!empty($rec_selected_cols['grid_logic']))
                {
                    if($rec_selected_cols['grid_logic_with_leftjoin'] == 1)
                    {                       
                        if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                        {                                                   
                        $join_alais = $join_alais+1;
                        $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." ";
                        $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        }
                    }
                    else
                    {
                        //req_skills left join is not using in any of the selected columns so we are removing leftjoin
                        if (((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1)))
                        {                                                   
                            $join_alais = $join_alais+1;
                            if(!($modulename == 'job orders' && $rec_selected_cols['ref_table'] == 'req_skills')){
                                 $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." ";
                                
                            }
                            $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                           
                        }
                    }


                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                    {                       
                        $fields .= "IF(DATE_FORMAT(".str_replace('@^@',$array_table[$rec_selected_cols['ref_table']],str_replace("@|@|@|@|@",$username,$rec_selected_cols['grid_logic'])).", '%m/%d/%Y') != '00/00/0000', DATE_FORMAT(DATE(STR_TO_DATE(".$array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'].",'%m/%d/%Y')), '%m/%d/%Y'), ''), ";
                    }
                    else
                    {
                        $fields .= str_replace('@^@',$array_table[$rec_selected_cols['ref_table']],str_replace("@|@|@|@|@",$username,$rec_selected_cols['grid_logic'])).", ";
                    }
                    
                }
                elseif(!empty($rec_selected_cols['db_col_type']))
                {
                    if($rec_selected_cols['db_col_type'] == 'date')
                    {
                        $dummyfieldname = 't'.$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                        $fields .= tzRetQueryStringDTime($dummyfieldname,'Date','/')." as ".$rec_selected_cols['ref_target_column_name'].", ";
                        if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                        {                                                    
                        $join_alais = $join_alais+1;
                        $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." ";
                        $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        }
                    }
                    if($rec_selected_cols['db_col_type'] == 'regular_date')
                    {
                        $dummyfieldname = 't'.$join_alais.".".$rec_selected_cols['ref_target_column_name'];
                        $fields .= tzRetQueryStringSelBoxDate($dummyfieldname,'Date','/')." as ".$rec_selected_cols['ref_target_column_name'].", ";
                        if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                        {                                                    
                        $join_alais = $join_alais+1;
                        $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." ";
                        $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        }
                    }                   
                }
                else
                {
                    // We have a mysql function in join comparision on this column
                    if($modulename == 'candidates' && $rec_selected_cols['column_name'] == "Employee ID")
                        $function_in_ON_condition = 1;
                        
                        if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                        {                                           
                            $join_alais = $join_alais+1;
        
                            if(isset($function_in_ON_condition))
                            {
                            unset($function_in_ON_condition);
                            $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON ".$rec_selected_cols['db_col_name']." = t".$join_alais.".".$rec_selected_cols['ref_column_name']." ";
                            }
                             // Adding active condition for the below two columns and don't remove space after bill_pay_terms it was inserted like that earlier in db
                            else if($modulename == 'job orders' && $rec_selected_cols['ref_table'] == 'workerscomp'){
                                $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." AND t".$join_alais.".status = 'Active'";
                            }
                            else if ($modulename == 'job orders' && $rec_selected_cols['ref_table'] == 'bill_pay_terms ')
                            {
                                $left_joins .= "LEFT JOIN " . $rec_selected_cols['ref_table'] . " t" . $join_alais . " ON t" . $join_alais . "." . $rec_selected_cols['ref_column_name'] . " = " . $mod_details['primary_table'] . "." . $rec_selected_cols['db_col_name'] . " AND t" . $join_alais . ".billpay_status = 'active'";
                            }
                            else{
                                if(!($modulename == 'job orders' && $rec_selected_cols['ref_table'] == 'req_skills')){
                                $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." = t".$join_alais.".".$rec_selected_cols['ref_column_name']." ";
                                
                                }
                            }

                            $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        }
                    
                    $fields .= $array_table[$rec_selected_cols['ref_table']].".".$rec_selected_cols['ref_target_column_name'].", ";
                }
            }
            else
            {
                if(!empty($rec_selected_cols['grid_logic']))
                {
                    $fields .= str_replace('@^@',$array_table[$rec_selected_cols['ref_table']],str_replace("@|@|@|@|@",$username,$rec_selected_cols['grid_logic'])).", ";   
                }
                elseif($rec_selected_cols['db_col_type'] == 'date')
                {
                    $dummyfieldname = $mod_details['primary_table'].".".$rec_selected_cols['db_col_name'];                  
                    $fields .= tzRetQueryStringDTime($dummyfieldname, 'Date', '/')." as ".$rec_selected_cols['db_col_name'].", ";
                }
                elseif($rec_selected_cols['db_col_type'] == 'regular_date')
                {
                    $dummyfieldname = $mod_details['primary_table'].".".$rec_selected_cols['db_col_name'];                  
                    $fields .= tzRetQueryStringSelBoxDate($dummyfieldname, 'Date', '/')." as ".$rec_selected_cols['db_col_name'].", ";
                }
                else
                {
                    $fields .= $mod_details['primary_table'].".".$rec_selected_cols['db_col_name'].", ";
                }
            }
                }
                $username = $userid;
                
                $sel_grid_content = "select ".substr($fields,0, strlen($fields)-2)." from ".$mod_details['primary_table']." ".$left_joins." where 1=1";
                
                if($modulename == 'contacts')
                        $sel_grid_content .= " AND ".$mod_details['primary_table'].".status= 'ER' AND (FIND_IN_SET('".$username."',".$mod_details['primary_table'].".accessto)>0 OR ".$mod_details['primary_table'].".owner='".$username."' OR ".$mod_details['primary_table'].".accessto='ALL')";
                if($modulename == 'companies')
                        $sel_grid_content .= " AND ".$mod_details['primary_table'].".status= 'ER' AND (".$mod_details['primary_table'].".owner = '".$username."' OR FIND_IN_SET( '".$username."', ".$mod_details['primary_table'].".accessto ) >0 OR ".$mod_details['primary_table'].".accessto = 'ALL') AND ".$mod_details['primary_table'].".crmcompany='Y'";
                if($modulename == 'candidates')
                        $sel_grid_content .= " AND ".$mod_details['primary_table'].".status= 'ACTIVE'  AND (".$mod_details['primary_table'].".owner='".$username."' OR FIND_IN_SET('".$username."',".$mod_details['primary_table'].".accessto )>0 OR ".$mod_details['primary_table'].".accessto='ALL') ";
                if($modulename == 'job orders')
                        $sel_grid_content .= " AND (".$mod_details['primary_table'].".owner='".$username."' OR FIND_IN_SET('".$username."',".$mod_details['primary_table'].".accessto)>0 OR ".$mod_details['primary_table'].".accessto='all') AND ".$mod_details['primary_table'].".status IN ('approve','Accepted')";
                if($modulename == 'Opportunities'){
                    $sel_grid_content = "select ".substr($fields,0, strlen($fields)-2).",csno from ".$mod_details['primary_table']." ".$left_joins." where 1=1";
                    $sel_grid_content .= " AND ".$mod_details['primary_table'].".oppr_status= 'ACTIVE' ";
                }
                        
                        
                /*
        echo "<pre>";      
                print_r($array_table);
                echo "</pre>";
                */
        
                @eval("\$sel_grid_content = \"$sel_grid_content\";");
                return $sel_grid_content;
        }
    
    ///////////////////////////////////////////////////////////////////////////////////////////////
    /////////// FUNCTION TO BUILD QUERY TO GET TOTAL GRID ROWS WITH SELECTED COLUMNS /////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    function countQueryBuilder($modulename, $userid, $cvsid=0)
    {   
        global $db;
        $mod_details = $this->getModuleid_new($modulename);
        if($modulename == 'job orders')
            $fields = "count(".$mod_details['primary_table'].".posid)";
        else
            $fields = "count(".$mod_details['primary_table'].".sno)";
        
        
        $res_selected_cols = $this->selectedColsQuery($modulename,$userid,$cvsid);      
        $left_joins = '';   
        $join_alais = 0;
        $array_table = array();
        
        while($rec_selected_cols = mysql_fetch_assoc($res_selected_cols))   
        {   
            if(!empty($rec_selected_cols['ref_table']))
            {                   
                if(!empty($rec_selected_cols['db_col_type']))
                {
                    if($rec_selected_cols['db_col_type'] == 'date')
                    {
                        
                        if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                        {                                                    
                            $join_alais = $join_alais+1;
                            $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." ";
                            $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        }
                    }
                    if($rec_selected_cols['db_col_type'] == 'regular_date')
                    {
                        
                        if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                        {                                                    
                            $join_alais = $join_alais+1;
                            $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." ";
                            $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        }
                    }
                    if($rec_selected_cols['db_col_type'] == 'udf_date')
                    {
                        
                        if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                        {                                                    
                            $join_alais = $join_alais+1;
                            $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." ";
                            $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        }
                    }
                    
                    
                }
                else
                {                   
                    // We have a mysql function in join comparision on this column
                    if($modulename == 'candidates' && $rec_selected_cols['column_name'] == "Employee ID")
                    {
                        $function_in_ON_condition = 1;
                    }
                        
                    if((array_key_exists($rec_selected_cols['ref_table'], $array_table) === false) || ($rec_selected_cols['allow_leftjoin'] == 1))
                    {                                           
                        $join_alais = $join_alais+1;
    
                        if(isset($function_in_ON_condition))
                        {
                        
                        $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON ".$rec_selected_cols['db_col_name']." = t".$join_alais.".".$rec_selected_cols['ref_column_name']." ";
                        }
                         // Adding active condition for the below two columns and don't remove space after bill_pay_terms it was inserted like that earlier in db
                        else if($modulename == 'job orders' && $rec_selected_cols['ref_table'] == 'workerscomp'){
                            $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON t".$join_alais.".".$rec_selected_cols['ref_column_name']." = ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." AND t".$join_alais.".status = 'Active'";
                        }

                        else if ($modulename == 'job orders' && $rec_selected_cols['ref_table'] == 'bill_pay_terms ')
                        {
                            $left_joins .= "LEFT JOIN " . $rec_selected_cols['ref_table'] . " t" . $join_alais . " ON t" . $join_alais . "." . $rec_selected_cols['ref_column_name'] . " = " . $mod_details['primary_table'] . "." . $rec_selected_cols['db_col_name'] . " AND t" . $join_alais . ".billpay_status = 'active'";
                        }
                        else
                            if (!($modulename == 'job orders' && $rec_selected_cols['ref_table'] == 'req_skills'))
                            {                                                   
                                $left_joins .= "LEFT JOIN ".$rec_selected_cols['ref_table']." t".$join_alais." ON ".$mod_details['primary_table'].".".$rec_selected_cols['db_col_name']." = t".$join_alais.".".$rec_selected_cols['ref_column_name']." ";
                            }

                         $array_table[$rec_selected_cols['ref_table']] = "t".$join_alais;
                        unset($function_in_ON_condition);
                    }
                }
            }           
        }
        $username = $userid;
        
        $sel_grid_content = "select ".$fields." from ".$mod_details['primary_table']." ".$left_joins." where 1=1";
                
                if($modulename == 'contacts')
                        $sel_grid_content .= " AND ".$mod_details['primary_table'].".status= 'ER' AND (FIND_IN_SET('".$username."',".$mod_details['primary_table'].".accessto)>0 OR ".$mod_details['primary_table'].".owner='".$username."' OR ".$mod_details['primary_table'].".accessto='ALL')";
                if($modulename == 'companies')
                        $sel_grid_content .= " AND ".$mod_details['primary_table'].".status= 'ER' AND (".$mod_details['primary_table'].".owner = '".$username."' OR FIND_IN_SET( '".$username."', ".$mod_details['primary_table'].".accessto ) >0 OR ".$mod_details['primary_table'].".accessto = 'ALL') AND ".$mod_details['primary_table'].".crmcompany='Y'";
                if($modulename == 'candidates')
                        $sel_grid_content .= " AND ".$mod_details['primary_table'].".status= 'ACTIVE'  AND (".$mod_details['primary_table'].".owner='".$username."' OR FIND_IN_SET('".$username."',".$mod_details['primary_table'].".accessto )>0 OR ".$mod_details['primary_table'].".accessto='ALL') ";
                if($modulename == 'job orders')
                        $sel_grid_content .= " AND (".$mod_details['primary_table'].".owner='".$username."' OR FIND_IN_SET('".$username."',".$mod_details['primary_table'].".accessto)>0 OR ".$mod_details['primary_table'].".accessto='all') AND ".$mod_details['primary_table'].".status IN ('approve','Accepted')";
                if($modulename == 'Opportunities')
                        $sel_grid_content .= " AND ".$mod_details['primary_table'].".oppr_status= 'ACTIVE' ";
            
        @eval("\$sel_grid_content = \"$sel_grid_content\";");
        return $sel_grid_content;
    }
    
    
    function gridBuilder($modulename)
    {
        global $db;
        $sel_grid_contents = $this->queryBuilder($modulename);      
        $res_grid_contents = mysql_query($sel_grid_contents, $db);

        $i = 0;
        
        while($rec_grid_contents = mysql_fetch_array($res_grid_contents))
        {
            echo "<tr>";
            $res_selected_cols = $this->selectedColsQuery($modulename);
            while($rec_selected_cols = mysql_fetch_array($res_selected_cols))
            {
                echo "<td>".$rec_grid_contents[$rec_selected_cols['db_col_name']]."</td>";
            }           
            echo "</tr>";           
        }       
    }
    
    //////////////////////////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO Fetch column names for the manage shortlist customize view. /////////
    //////////////////////////////////////////////////////////////////////////////////////////  
    
    function getCustomizeColumns($strModuleName,$strUserName){
        // Initializing the variables       
        global $db;$inti = 0;$intj = 0;$strColumnNames = '';$strLeftJoin = '';  $inttable = 1;$finalQryStr ='';$finalSelQryFetchCnt = '';
        $strPrimaryTable    = 'candidate_list';
        $strSecondaryTable  = 'short_lists';
        $strThirdTable      = 'candidate_pref';
        $strSelQry      = "SELECT id,custom_form_modules_id FROM udv_grids WHERE grid_name = '".$strModuleName."' AND cuser = '".$strUserName."'";       
        $resSelQry = mysql_query($strSelQry,$db);       
        if(mysql_num_rows($resSelQry) > 0 )
        {
            $objSelQry  = mysql_fetch_object($resSelQry);
            $strSelQryOne   = "SELECT custom_grid_column_id,column_order FROM udv_grids_usercolumns WHERE c_user_id='".$strUserName."' AND custom_grid_id = '".$objSelQry->id."' ORDER BY column_order ASC";    
            $resSelQryOne   = mysql_query($strSelQryOne,$db);
            if(mysql_num_rows($resSelQryOne) > 0)
            {           
                while($objSelQryOne = mysql_fetch_object($resSelQryOne)){
                    $intColumnId = $objSelQryOne->custom_grid_column_id;
                    $selQryTwo =    "SELECT 
                                        column_name,db_col_name,ref_table,ref_column_name,ref_target_column_name,
                                        allow_leftjoin,grid_logic,col_alias,search_logic,db_col_type,defaultflag,
                                        defaultorder,allow_on_grid
                                    FROM 
                                        udv_grid_columns 
                                    WHERE 
                                        udfstatus = 1 AND id = '".$intColumnId."'";
                    $resSelQryTwo = mysql_query($selQryTwo,$db);
                    
                    while($objSelQryTwo = mysql_fetch_object($resSelQryTwo)){
                        // Logic wise binding the first time.
                        if($inttable == 1){                          
                            $strLeftJoin = " LEFT JOIN ".$strSecondaryTable." ON ".$strSecondaryTable.".candid=candidate_list.sno"; 
                            $strLeftJoin .= " LEFT JOIN ".$strThirdTable." ON ".$strThirdTable.".username=candidate_list.username";                                     
                        }
                        /*
                            Based on allow_leftjoin 0,1. We are building column in the query
                            0 = Here we have two condition, If grid_logic is not null, we are considering alias values from table->col_alias
                            1 = Dynamically building the tables alias values.
                        */                      
                        
                        if($objSelQryTwo->allow_leftjoin == 0){
                            if($objSelQryTwo->grid_logic != ''){
                                if($objSelQryTwo->col_alias == 'accessto' || $objSelQryTwo->col_alias == 'candtype' ){
                                    if($objSelQryTwo->col_alias == 'accessto'){
                                        $gridLogic = str_replace("ACCESS_TO", $strUserName, $objSelQryTwo->grid_logic);
                                    }else{
                                        $gridLogic = str_replace("CAND_TYPE", $strUserName, $objSelQryTwo->grid_logic);
                                    }                                   
                                    $strColumnNames .= $gridLogic.' AS '. $objSelQryTwo->col_alias.','; 
                                }else{
                                    $strColumnNames .= $objSelQryTwo->grid_logic.' AS '. $objSelQryTwo->col_alias.',';  
                                }   
                            }else{
                                $strColumnNames .= $strPrimaryTable.'.'.$objSelQryTwo->db_col_name.',';
                            }   
                        }else{                          
                            if($objSelQryTwo->ref_table != '' && $objSelQryTwo->ref_target_column_name !='' ){
                                if($objSelQryTwo->ref_table != 'candidate_pref' ){
                                                                    if($objSelQryTwo->ref_table == 'candidate_prof'){
                                                                            if($objSelQryTwo->ref_target_column_name == 'availsdate'){
                                                                                $tableAliasName = 't'.$inttable;
                                                                                $strColumnNames .="IF(".$tableAliasName.".availsdate = 'inactive', 'Inactive', IF((".$tableAliasName.".availsdate = '0-0-0' OR ".$tableAliasName.".availsdate = '' OR ".$tableAliasName.".availsdate IS NULL OR ".$tableAliasName.".availsdate = 'immediate'),'Immediate', IF((datediff(".$tableAliasName.".availsdate, now())) < 0,'Immediate', DATE_FORMAT(STR_TO_DATE(".$tableAliasName.".availsdate, '%Y-%m-%d'), '%m/%d/%Y')))),";
                                                                            }else if($objSelQryTwo->ref_target_column_name == 'availedate'){
                                                                                $tableAliasName = 't'.$inttable;
                                                                                $strColumnNames .="IF((".$tableAliasName.".availedate = '0-0-0' OR ".$tableAliasName.".availedate = '' OR ".$tableAliasName.".availedate IS NULL),'' ,DATE_FORMAT(STR_TO_DATE(".$tableAliasName.".availedate, '%Y-%m-%d'), '%m/%d/%Y')),";
                                                                            }else{
                                                                               $strColumnNames .= 't'.$inttable.'.'.$objSelQryTwo->ref_target_column_name .','; 
                                                                            }
                                                                        }else{
                                                                            $strColumnNames .= 't'.$inttable.'.'.$objSelQryTwo->ref_target_column_name .',';
                                                                        }
                                }else{
                                    $strColumnNames .= $strThirdTable.'.'.$objSelQryTwo->ref_target_column_name .',';   
                                }   
                            }   
                            // Dynamic Left Join string buinding.
                            if($objSelQryTwo->db_col_name != 'candidate_list.sno' ){                                
                                if($objSelQryTwo->ref_table != 'candidate_pref' ){
                                    $strLeftJoin .= " LEFT JOIN ".$objSelQryTwo->ref_table." t".$inttable." ON "." t".$inttable.'.'.$objSelQryTwo->ref_column_name."=".$objSelQryTwo->db_col_name;
                                }                                   
                            }else{
                                if($objSelQryTwo->ref_table == 'udf_form_details_candidate_values' ){
                                    $strLeftJoin .= " LEFT JOIN ".$objSelQryTwo->ref_table." t".$inttable." ON "." t".$inttable.'.'.$objSelQryTwo->ref_column_name."=".$objSelQryTwo->db_col_name;
                                }
                            }                       
                        }   
                        $inttable++;                                        
                    }// end of while        
                }//end of while 
                
                $strColumnNames = substr($strColumnNames,0,-1);                 
            }// end of if               
        }// end of if   
        
        if($strColumnNames != ''){
            if($strColumnNames != '' && $strLeftJoin !=''){
                
                $finalQrySel = " SELECT candidate_list.sno, candidate_list.username,candidate_list.resid,IF(shift_setup.sno IS NULL,0,shift_setup.sno) AS shiftid,".$strColumnNames." ,cm.score as score FROM ".$strPrimaryTable." ".$strLeftJoin;
                $finalSelQryFetchCnt = " SELECT count(DISTINCT CONCAT($strPrimaryTable.sno,'-',$strSecondaryTable.shift_id)) FROM ".$strPrimaryTable." ".$strLeftJoin;              
                return  $finalQrySel."@@@".$finalSelQryFetchCnt;
                
            }else{
                
                $strLeftJoin = " LEFT JOIN ".$strSecondaryTable." ON ".$strSecondaryTable.".candid=candidate_list.sno"; 
                $finalQryStr = " SELECT candidate_list.sno, candidate_list.username,candidate_list.resid,IF(shift_setup.sno IS NULL,0,shift_setup.sno) AS shiftid, ".$strColumnNames." ,cm.score as score,IF(shift_setup.sno IS NULL,0,shift_setup.sno) AS shiftid FROM ".$strPrimaryTable." ".$strLeftJoin;
                
                $finalSelQryFetchCnt = " SELECT count(DISTINCT CONCAT($strPrimaryTable.sno,'-',$strSecondaryTable.shift_id)) FROM ".$strPrimaryTable." ".$strLeftJoin;  ;               
                return  $finalQryStr."@@@".$finalSelQryFetchCnt;
            }
        }else{
            return $finalQryStr; 
        }           
    }// end of the function.
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO Fetch column names for the manage shortlist customize view when schedule search filters also selected. /////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    function getCustomizeColumnsWithScheduleSearchFilters($strModuleName,$strUserName, $selTabName){
        //echo "in filters";
        // Initializing the variables       
        global $db;$inti = 0;$intj = 0;$strColumnNames = '';$strLeftJoin = '';  $inttable = 1;$finalQryStr ='';$finalSelQryFetchCnt = '';
        
        $strPrimaryTable = 'candidate_list';$strSecondaryTable = 'short_lists';$strThirdTable = 'candidate_pref';$strCandShiftsTable = 'candidate_sm_timeslots';

        if($selTabName == "schedulesearch"){                
            $strShiftScheduleColumnNames = " , GROUP_CONCAT(DISTINCT DATE_FORMAT(candidate_sm_timeslots.shift_date,'%m/%d/%Y') ORDER BY candidate_sm_timeslots.shift_date) AS available_dates ";
        }
        
        $strLeftJoin = " LEFT JOIN ".$strCandShiftsTable." ON ".$strCandShiftsTable.".username = ".$strPrimaryTable.".username ";       
        
        $strSelQry = "SELECT id,custom_form_modules_id FROM udv_grids WHERE grid_name = '".$strModuleName."' AND cuser = '".$strUserName."'";        
        $resSelQry = mysql_query($strSelQry,$db);       
        if(mysql_num_rows($resSelQry) > 0 ){
            $objSelQry = mysql_fetch_object($resSelQry);
            $strSelQryOne = "SELECT custom_grid_column_id,column_order FROM udv_grids_usercolumns WHERE c_user_id='".$strUserName."' AND custom_grid_id = '".$objSelQry->id."' ORDER BY column_order ASC";  
            $resSelQryOne = mysql_query($strSelQryOne,$db);
            if(mysql_num_rows($resSelQryOne) > 0){          
                while($objSelQryOne = mysql_fetch_object($resSelQryOne)){
                    $intColumnId = $objSelQryOne->custom_grid_column_id;
                    $selQryTwo =    "SELECT 
                                        column_name,db_col_name,ref_table,ref_column_name,ref_target_column_name,
                                        allow_leftjoin,grid_logic,col_alias,search_logic,db_col_type,defaultflag,
                                        defaultorder,allow_on_grid
                                    FROM 
                                        udv_grid_columns 
                                    WHERE 
                                        udfstatus = 1 AND id = '".$intColumnId."'";
                    $resSelQryTwo = mysql_query($selQryTwo,$db);
                    
                    while($objSelQryTwo = mysql_fetch_object($resSelQryTwo)){
                        // Logic wise binding the first time.
                        if($inttable == 1){                          
                            $strLeftJoin .= " LEFT JOIN ".$strSecondaryTable." ON ".$strSecondaryTable.".candid=candidate_list.sno"; 
                            $strLeftJoin .= " LEFT JOIN ".$strThirdTable." ON ".$strThirdTable.".username=candidate_list.username";                                     
                        }
                        /*
                            Based on allow_leftjoin 0,1. We are building column in the query
                            0 = Here we have two condition, If grid_logic is not null, we are considering alias values from table->col_alias
                            1 = Dynamically building the tables alias values.
                        */                      
                        
                        if($objSelQryTwo->allow_leftjoin == 0){
                            if($objSelQryTwo->grid_logic != ''){
                                if($objSelQryTwo->col_alias == 'accessto' || $objSelQryTwo->col_alias == 'candtype' ){
                                    if($objSelQryTwo->col_alias == 'accessto'){
                                        $gridLogic = str_replace("ACCESS_TO", $strUserName, $objSelQryTwo->grid_logic);
                                    }else{
                                        $gridLogic = str_replace("CAND_TYPE", $strUserName, $objSelQryTwo->grid_logic);
                                    }                                   
                                    $strColumnNames .= $gridLogic.' AS '. $objSelQryTwo->col_alias.','; 
                                }else{
                                    $strColumnNames .= $objSelQryTwo->grid_logic.' AS '. $objSelQryTwo->col_alias.',';  
                                }   
                            }else{
                                $strColumnNames .= $strPrimaryTable.'.'.$objSelQryTwo->db_col_name.',';
                            }   
                        }else{                          
                            if($objSelQryTwo->ref_table != '' && $objSelQryTwo->ref_target_column_name !='' ){
                                if($objSelQryTwo->ref_table != 'candidate_pref' ){
                                    $strColumnNames .= 't'.$inttable.'.'.$objSelQryTwo->ref_target_column_name .',';                            
                                }else{
                                    $strColumnNames .= $strThirdTable.'.'.$objSelQryTwo->ref_target_column_name .',';   
                                }   
                            }   
                            // Dynamic Left Join string buinding.
                            if($objSelQryTwo->db_col_name != 'candidate_list.sno' ){                                
                                if($objSelQryTwo->ref_table != 'candidate_pref' ){
                                    $strLeftJoin .= " LEFT JOIN ".$objSelQryTwo->ref_table." t".$inttable." ON "." t".$inttable.'.'.$objSelQryTwo->ref_column_name."=".$objSelQryTwo->db_col_name;
                                }                                   
                            }else{
                                if($objSelQryTwo->ref_table == 'udf_form_details_candidate_values' ){
                                    $strLeftJoin .= " LEFT JOIN ".$objSelQryTwo->ref_table." t".$inttable." ON "." t".$inttable.'.'.$objSelQryTwo->ref_column_name."=".$objSelQryTwo->db_col_name;
                                }
                            }                       
                        }   
                        $inttable++;                                        
                    }// end of while        
                }//end of while 
                
                $strColumnNames = substr($strColumnNames,0,-1);                 
            }// end of if               
        }// end of if   
        
        if($strColumnNames != ''){
            if($strColumnNames != '' && $strLeftJoin !=''){
                $finalQrySel = " SELECT candidate_list.sno, candidate_list.username,candidate_list.resid, ".$strColumnNames.$strShiftScheduleColumnNames." FROM ".$strPrimaryTable." ".$strLeftJoin;
                $finalSelQryFetchCnt = " SELECT count(1) FROM ".$strPrimaryTable." ".$strLeftJoin;              
                return  $finalQrySel."@@@".$finalSelQryFetchCnt;
                
            }else{
                $strLeftJoin = " LEFT JOIN ".$strSecondaryTable." ON ".$strSecondaryTable.".candid=candidate_list.sno"; 
                $finalQryStr = " SELECT candidate_list.sno, candidate_list.username,candidate_list.resid, ".$strColumnNames.$strShiftScheduleColumnNames." FROM ".$strPrimaryTable." ".$strLeftJoin;
                
                $finalSelQryFetchCnt = " SELECT count(1) FROM ".$strPrimaryTable." ".$strLeftJoin;  ;               
                return  $finalQryStr."@@@".$finalSelQryFetchCnt;
            }
        }else{
            return $finalQryStr; 
        }           
    }// end of the function.
    
    
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO fetch columns, based on keyword on the grid in the manage shortlist customize view. /////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////  
    function getAsFields($strModuleName,$strUserName){              
        global $db;
        $strSelQry = "SELECT id,custom_form_modules_id FROM udv_grids WHERE grid_name = '".$strModuleName."' AND cuser = '".$strUserName."'";        
        $resSelQry = mysql_query($strSelQry,$db);
        $strPrimaryTable = 'candidate_list';$strThirdTable = 'candidate_pref';
        $inti = 0;$intj = 0;$strColumnNames = '';$strLeftJoin = ''; $inttable = 1;$finalQryStr ='';$finalSelQryFetchCnt = '';
        if(mysql_num_rows($resSelQry) > 0 ){
            $objSelQry = mysql_fetch_object($resSelQry);
            $strSelQryOne = "SELECT 
                                        ugc.column_name,ugc.db_col_name,uguc.column_order,ugc.ref_table,ugc.ref_column_name,
                                        ugc.ref_target_column_name,ugc.col_alias,ugc.grid_logic ,ugc.allow_leftjoin
                                    FROM 
                                        udv_grids_usercolumns uguc, udv_grid_columns ugc  
                                    WHERE
                                        ugc.udfstatus = 1 AND uguc.custom_grid_column_id = ugc.id   
                                        AND uguc.c_user_id='".$strUserName."' 
                                        AND uguc.custom_grid_id = '".$objSelQry->id."'  ORDER BY uguc.column_order ASC";    
            $resSelQryOne = mysql_query($strSelQryOne,$db);         
            while($objSelQryTwo = mysql_fetch_object($resSelQryOne)){                                   
                    if($objSelQryTwo->allow_leftjoin == 0){                
                        if($objSelQryTwo->grid_logic != ''){
                            if($objSelQryTwo->col_alias == 'accessto' || $objSelQryTwo->col_alias == 'candtype' ){
                                if($objSelQryTwo->col_alias == 'accessto')
                                    $gridLogic = str_replace("ACCESS_TO", $strUserName, $objSelQryTwo->grid_logic);
                                else
                                    $gridLogic = str_replace("CAND_TYPE", $strUserName, $objSelQryTwo->grid_logic);
                                $strColumnNames .= $gridLogic.'@@@';
                            }else{
                                $strColumnNames .= $objSelQryTwo->grid_logic.'@@@';
                            }   
                        }else{
                            $strColumnNames .= $strPrimaryTable.'.'.$objSelQryTwo->db_col_name.'@@@';
                        }   
                    }else{                      
                        if($objSelQryTwo->ref_table != '' && $objSelQryTwo->ref_target_column_name !='' ){                  
                            if($objSelQryTwo->ref_table != 'candidate_pref' ){
                                                            if($objSelQryTwo->ref_table == 'candidate_prof' ){
                                                                if($objSelQryTwo->ref_target_column_name == 'availsdate'){
                                                                        $tableAliasName = 't'.$inttable;
                                                                        $strColumnNames .="IF(".$tableAliasName.".availsdate = 'inactive', 'Inactive', IF((".$tableAliasName.".availsdate = '0-0-0' OR ".$tableAliasName.".availsdate = '' OR ".$tableAliasName.".availsdate IS NULL OR ".$tableAliasName.".availsdate = 'immediate'),'Immediate', IF((datediff(".$tableAliasName.".availsdate, now())) < 0,'Immediate', DATE_FORMAT(STR_TO_DATE(".$tableAliasName.".availsdate, '%Y-%m-%d'), '%m/%d/%Y'))))@@@";
                                                                    }else if($objSelQryTwo->ref_target_column_name == 'availedate'){
                                                                        $tableAliasName = 't'.$inttable;
                                                                        $strColumnNames .="IF((".$tableAliasName.".availedate = '0-0-0' OR ".$tableAliasName.".availedate = '' OR ".$tableAliasName.".availedate IS NULL),'' ,DATE_FORMAT(STR_TO_DATE(".$tableAliasName.".availedate, '%Y-%m-%d'), '%m/%d/%Y'))@@@";
                                                                    }else{
                                                                       $strColumnNames .= 't'.$inttable.'.'.$objSelQryTwo->ref_target_column_name .'@@@';
                                                                    }
                                                                }else{
                                                                    $strColumnNames .= 't'.$inttable.'.'.$objSelQryTwo->ref_target_column_name .'@@@';
                                                                }
                            }else{
                                $strColumnNames .= $strThirdTable.'.'.$objSelQryTwo->ref_target_column_name .'@@@'; 
                            }                               
                        }                               
                    }           
                $inttable++;                    
            }
            $strColumnNames = substr($strColumnNames,0,-3);             
             if($strColumnNames !=''){
                 $arrColumnNames = explode('@@@',$strColumnNames);
                 array_unshift($arrColumnNames, "");    
                 return $arrColumnNames;    
            }else{
                return $strColumnNames;
            }       
        }// end of if   
    }// end of the function 

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////// FUNCTION TO Fetch sorting order for the manage shortlist customize view. /////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////    
    function getSsFields($strModuleName,$strUserName){              
        global $db; 
        $strSelQry = "SELECT id,custom_form_modules_id FROM udv_grids WHERE grid_name = '".$strModuleName."' AND cuser = '".$strUserName."'";        
        $resSelQry = mysql_query($strSelQry,$db);
        $strPrimaryTable = 'candidate_list';$strThirdTable = 'candidate_pref';
        $inti = 0;$intj = 0;$strColumnNames = '';$strLeftJoin = ''; $inttable = 1;$finalQryStr ='';$finalSelQryFetchCnt = '';
        if(mysql_num_rows($resSelQry) > 0 ){
            $objSelQry = mysql_fetch_object($resSelQry);
            $strSelQryOne = "SELECT 
                                        ugc.column_name,ugc.db_col_name,uguc.column_order,ugc.ref_table,ugc.ref_column_name,
                                        ugc.ref_target_column_name,ugc.col_alias,ugc.grid_logic ,ugc.allow_leftjoin
                                    FROM 
                                        udv_grids_usercolumns uguc, udv_grid_columns ugc  
                                    WHERE
                                        ugc.udfstatus = 1 AND uguc.custom_grid_column_id = ugc.id   
                                        AND uguc.c_user_id='".$strUserName."' 
                                        AND uguc.custom_grid_id = '".$objSelQry->id."'  ORDER BY uguc.column_order ASC";    
            $resSelQryOne = mysql_query($strSelQryOne,$db);                 
             while($objSelQryTwo = mysql_fetch_object($resSelQryOne)){                                  
                    if($objSelQryTwo->allow_leftjoin == 0){                
                        if($objSelQryTwo->grid_logic != ''){
                            $strColumnNames .= $objSelQryTwo->col_alias.'@@@';
                        }else{
                            $strColumnNames .= $strPrimaryTable.'.'.$objSelQryTwo->db_col_name.'@@@';
                        }   
                    }else{
                        
                        if($objSelQryTwo->ref_table != '' && $objSelQryTwo->ref_target_column_name !='' ){                  
                            if($objSelQryTwo->ref_table != 'candidate_pref' ){
                                                                if($objSelQryTwo->ref_table == 'candidate_prof'){
                                                                    if($objSelQryTwo->ref_target_column_name == 'availsdate'){
                                                                        $tableAliasName = 't'.$inttable;
                                                                        $strColumnNames .="IF(".$tableAliasName.".availsdate = 'inactive', 'Inactive', IF((".$tableAliasName.".availsdate = '0-0-0' OR ".$tableAliasName.".availsdate = '' OR ".$tableAliasName.".availsdate IS NULL OR ".$tableAliasName.".availsdate = 'immediate'),'Immediate', IF((datediff(".$tableAliasName.".availsdate, now())) < 0,'Immediate', DATE_FORMAT(STR_TO_DATE(".$tableAliasName.".availsdate, '%Y-%m-%d'), '%m/%d/%Y'))))@@@";
                                                                    }else if($objSelQryTwo->ref_target_column_name == 'availedate'){
                                                                        $tableAliasName = 't'.$inttable;
                                                                        $strColumnNames .="IF((".$tableAliasName.".availedate = '0-0-0' OR ".$tableAliasName.".availedate = '' OR ".$tableAliasName.".availedate IS NULL),'' ,DATE_FORMAT(STR_TO_DATE(".$tableAliasName.".availedate, '%Y-%m-%d'), '%m/%d/%Y'))@@@";
                                                                    }else{
                                                                       $strColumnNames .= 't'.$inttable.'.'.$objSelQryTwo->ref_target_column_name .'@@@';
                                                                    }
                                                                }else{
                                                                    $strColumnNames .= 't'.$inttable.'.'.$objSelQryTwo->ref_target_column_name .'@@@';
                                                                }
                                                         }else{
                                $strColumnNames .= $strThirdTable.'.'.$objSelQryTwo->ref_target_column_name .'@@@'; 
                            }                               
                        }                               
                    }               
                $inttable++;                    
            } 
             $strColumnNames = substr($strColumnNames,0,-3);    
            
             if($strColumnNames !=''){
                 $arrColumnNames = explode('@@@',$strColumnNames);
                 array_unshift($arrColumnNames, "");    
                 return $arrColumnNames;    
            }else{
                return $strColumnNames;
            }        
        }// end of if   
    }// end of the function         
    
}
?>