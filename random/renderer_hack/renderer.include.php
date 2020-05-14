        // **************************** cca start ******************************
        // Random plugin 
        // **************************** cca start ******************************
        global $DB, $CFG, $USER;        
        
        // Ak je povolene zadanie typu Random 
        // If the type Random is enabled
        $plugin_random = $DB->get_record('assign_plugin_config', array('assignment' => $header->assign->id, 'plugin' => 'random'), 'value');
        
        // Kontrola priradenia/Priradenie suboru studentovi (ucitelovi sa subor nepriraduje)
        // Check/assign file to student (not to teacher)
        if($plugin_random->value && !has_capability('mod/assign:grade', $header->context)) {
 
            // nacitanie informacii o ulozenych suboroch so zadaniami
            // read information about stored files with assignments
            $fs = get_file_storage();
            $context = $header->context;
            $files = $fs->get_area_files($context->id, 'assignsubmission_random', 'inputfiles', false, '', false);
    
            // ak existuju subory so zadaniami
            // if there are assignment files
            if($files) {
                // zistenie uz priradenych suborov z vybraneho adresara
                // find files already assigned
                $file_array_db = array();
                $records = $DB->get_records('assignsubmission_random', array('assignment' => $header->assign->id));     
                if ($records) {
                    foreach ($records as $records_value) {
                        if($records_value->file_assignment)  // este sa na to pozri a uprav to
                            $file_array_db[] = $records_value->file_assignment;
                    }
                }
    
                // zistenie, ci je priradeny subor daneho zadania uzivatelovi 
                // find whether the current user has a file
                $record = $DB->get_record('assignsubmission_random', array('assignment' => $header->assign->id, 'userid' => $USER->id));
    
                if(!isset($record->file_assignment)) {
                    // ak nie je priradeny subor, vyhlada volny a priradi ho (vlozenim do DB)
                    // if not file is assigned, find a free one, assign it (by SQL insert)
                   	$file_array = array();
                    $fs = get_file_storage();
                    $files = $fs->get_area_files($context->id, 'assignsubmission_random', 'inputfiles', false, '', false);
                    if (!empty($files)) {
                        foreach ($files as $f) {
                            $file_array[] = $f->get_filename();
                        }    
                    }  

                  	// ziskanie rozdielov poli suborov v DB a v adresari - vysledok je pole suborov, ktore este neboli priradene
                  	// difference between file array and DB - files not yet assigned
                  	$file_array_diff = array_diff ($file_array, $file_array_db);
                  	
                  	$file_select = '';
                  	if(count($file_array_diff)) {
                        // ak nie je pole prazdne, potom vyber subor, ktory je prvym prvkom pola
                        // if array not empty, select its first element
                        $keys = array_keys($file_array_diff); // keys of array elements
                        $file_select = $file_array_diff[$keys[0]];
                  	} else {
                        // ak je pole prazdne, t.j. vsetky subory uz boli priradene, potom sa priraduju subory nahodne
                        // nahodny vyber prvku
                        // if is array empty (all files assigned), make random assigment
                        // random selection
                        $item_random = intval(rand(0, count($file_array)-1));
                        $file_select = $file_array[$item_random];
                    }

                  	if($file_select) {
                  	    // zistenie, ci je uz nejaky zaznam v tabulke mdl_assignsubmission_random
                  	    // find if there are records in table mdl_assignsubmission_random
                  	    $record = $DB->get_record('assignsubmission_random', array('assignment' => $header->assign->id, 'userid' => $USER->id));
                        if(!$record) {
                            // ak neexistuje zaznam v tabulke mdl_assignsubmission_random => urob iba insert do mdl_assignsubmission_random
                            // if no record in mdl_assignsubmission_random => do only insert to mdl_assignsubmission_random
                  	        $data_insert = new stdClass();
                            $data_insert->userid          = $USER->id;
                            $data_insert->assignment      = $header->assign->id;
                            $data_insert->file_assignment = $file_select;
                            $data_insert->date_save       = time();
                            $DB->insert_record('assignsubmission_random', $data_insert);
                        } 
                  	}
                }
            }   
        } 
        // $_GET['action'] == 'grading' - in older versions 
        // $_GET['action'] == 'grade' - in newer versions 
        // if one of these is chosen => set hide_list to 1 <= not to show list in grader report
        $hide_list = 0;       
        if(isset($_GET['action'])) {
            if($_GET['action'] == 'grading' || $_GET['action'] == 'grade')
                $hide_list = 1;
        }
        
        if($plugin_random->value && !$hide_list) { 
            $o .= "<div class='box generalbox boxaligncenter boxwidthnormal' id='random_assignment_list'>\n";

            if(has_capability('mod/assign:grade', $header->context)) {

                $fs = get_file_storage();

                $files = $fs->get_area_files($header->context->id, 'assignsubmission_random', 'inputfiles', false, '', false);
                $url_list_in = array();
                if (!empty($files)) {
                    foreach ($files as $f) {
                        $url = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$header->context->id.'/assignsubmission_random/inputfiles/'.$f->get_filename());
                        $url_list_in[] = '<li><a href="'.$url.'">'.$f->get_filename().'</a></li>';
                    }    
                } 
                
                $files = $fs->get_area_files($header->context->id, 'assignsubmission_random', 'outputfiles', false, '', false);
                $url_list_out = array();
                if (!empty($files)) {
                    foreach ($files as $f) {
                        $url = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$header->context->id.'/assignsubmission_random/outputfiles/'.$f->get_filename());
                        $url_list_out[] = '<li><a href="'.$url.'">'.$f->get_filename().'</a></li>';
                    }    
                } 

                if($url_list_in || $url_list_out) {
                    //$o .= "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js'></script>\n";
                    $o .= "<script type='text/javascript'>\n";
                    $o .= "function cca_show_hide() {\n";
                    $o .= "  if(document.getElementById('cca_show_hide').checked)\n";
                    $o .= "    document.getElementById('id_cca_show_hide').style.display = 'block';\n";
                    $o .= "  else\n";
                    $o .= "    document.getElementById('id_cca_show_hide').style.display = 'none';\n";
                    $o .= "}\n";
                    $o .= "</script>\n";
                    $o .= "<div><input type='checkbox' id='cca_show_hide' value='1' onclick='cca_show_hide()' /> ".get_string('show_hide', 'assignsubmission_random')."</div>\n";
                    $o .= "<div id='id_cca_show_hide' style='display: none'>\n";
                    $o .= "<table class='boxaligncenter'>\n";
                    $o .= "<tr>\n";
                }

                if($url_list_in) {
                    asort($url_list_in);
                    $o .= "<td>\n";
                    $o .= "<p>".get_string('assignments', 'assignsubmission_random').":</p>\n";
                    $o .= "<ol>";
                    $o .= implode("",$url_list_in);
                    $o .= "</ol>\n";
                    $o .= "</td>\n";
                }
                
                if($url_list_out) {
                    asort($url_list_out);
                    $o .= "<td>\n";
                    $o .= "<p>".get_string('solutions', 'assignsubmission_random').":</p>\n";
                    $o .= "<ol>";
                    $o .= implode("",$url_list_out);
                    $o .= "</ol>\n";
                    $o .= "</td>\n";
                }

                if($url_list_in || $url_list_out) {
                    $o .= "</tr>";
                    $o .= "</table>";
                    $o .= "</div>";
                }
            }
            else
            {    
                $record = $DB->get_record('assignsubmission_random', array('assignment' => $header->assign->id, 'userid' => $USER->id));
                if($record)
                    $filename = $record->file_assignment;
                else
                    $filename = '';
                if($filename) {
                    $url = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$header->context->id.'/assignsubmission_random/inputfiles/'.$filename); 
                    $o .= get_string('getassignment','assignsubmission_random','<a href="'.$url.'" target="_blank">'.get_string('getassignmenttext','assignsubmission_random').'</a>');
                }
            }
            $o .= "\n</div>";
        }
        // **************************** cca stop *******************************
        // Random plugin 
        // **************************** cca stop *******************************       
