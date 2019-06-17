<?php
function va_todo_page ($person){
    ?>
    <script type="text/javascript">
	jQuery(function (){
		jQuery(document).on("change", ".va_todo_checkbox", function (){
			var row = jQuery(this).closest("tr");
			var checked =  jQuery(this).is(":checked");
			jQuery.post(ajax_object.ajaxurl, {
				"action" : "va",
				"namespace" : "util",
				"query" : "markTodo",
				"dbname" : "va_xxx",
				"id" : jQuery(this).data("id"),
				"marked" : checked? "1": "0"
			}, function (response){
				if(response == "success"){
					var parentRow;
					if(!row.hasClass("va_todo_parent")){
						if(row.prev().hasClass("va_todo_parent")){
							parentRow = row.prev();
						}
						else {
							parentRow = row.prevUntil(".va_todo_parent").last().prev();
						}
					}
					
					if(checked){
						row.find("td:not(.noCol)").css("background", "DarkSeaGreen");
						row.addClass("va_todo_ready");

						if(parentRow){
							var childrenNotReady = parentRow.nextUntil(".va_todo_parent").filter("tr:not(.va_todo_ready)");
							if (childrenNotReady.length == 0){
								console.log(parentRow.find("input.va_todo_checkbox"));
								parentRow.find("input.va_todo_checkbox").prop("disabled", false);
							}
						}
					}
					else {
						row.find("td:not(.noCol)").css("background", "white");
						row.removeClass("va_todo_ready");

						if(parentRow){
							parentRow.find("input[type=checkbox]").prop("disabled", true);
						}
					}
				}
			});
		});

		todoButtons();

		jQuery(document).on("va_todos_new", function (event, params){
			if(params["parent"] == -1){
				jQuery(".va_todo_table[data-context='" + params["context"] + "']").append(params["row"]);
				jQuery(".va_todo_parent_input").append(params["option"]);
			}
			else {
				var checkbox = jQuery(".va_todo_table[data-context='" + params["context"] + "'] tr input[data-id=" + params["parent"] + "]");
				console.log(checkbox);
				checkbox.attr("disabled", true);
				var parent = checkbox.closest("tr");

				var row;
				if (parent.next().length == 0 || parent.next().hasClass("va_todo_parent")){
					row = parent;
				}
				else {
					row = parent.nextUntil(".va_todo_parent").last();
				}
				row.after(params["row"]);
			}
		});
	});
    </script>
    <?php
    
    global $va_xxx;
    
    $todos = $va_xxx->get_results($va_xxx->prepare('
        SELECT Id_Todo, Todo, Kontext, Ueber, Fertig, Ueber IS NULL AND EXISTS (SELECT * FROM Todos t3 WHERE t3.Ueber = t.Id_Todo AND Fertig IS NULL) AS Blockiert
        FROM Todos t 
        WHERE Kuerzel=%s AND (Fertig IS NULL OR (Ueber IS NOT NULL AND (SELECT Fertig FROM Todos t2 WHERE t2.Id_Todo = t.Ueber) IS NULL)) 
        ORDER BY IF(Ueber IS NULL, Kontext, (SELECT Kontext FROM TODOS t4 WHERE t4.Id_Todo = t.Ueber)), IF(Ueber IS NULL, Id_Todo, Ueber) ASC, Ueber ASC, Erstellt ASC', $person), ARRAY_A);
    
    $last_context = NULL;
    $res = '';
    foreach ($todos as $key => $todo){
    	if(!$todo['Ueber'] && $todo['Kontext'] !== $last_context){
    		$last_context = $todo['Kontext'];
    		if($key != 0){
    			$res .= '</table>';
    		}
    		
    		$res .= '<h1>' . $last_context . '</h1><table class="va_todo_table" data-context="' . $todo['Kontext'] . '">';
    	}
        $res .= va_get_todo_row($todo);
    }
    $res .= '</table><br /><br />';
    
    $res .= va_get_todo_button($person);
    return $res;
}

function va_get_todo_button ($person){
    global $va_xxx;
    
    $res = '<input type="button" class="button button-primary va_add_todo_button" value="Todo hinzufügen" />';
    
    $res .= '<div class="va_todo_popup" style="display: none;">';
    $res .= '<input type="text" class="va_todo_text_input" style="width: 750px;" /><br />';
    $res .= '<input type="hidden" class="va_todo_owner_input" value="' . $person . '" />';
    $res .= '<select class="va_todo_parent_input" style="max-width: 800px;" autocomplete="off"><option value="-1"></option>';
    
    $top_level = $va_xxx->get_results($va_xxx->prepare('SELECT Id_Todo, Todo, Kontext FROM Todos WHERE Kuerzel=%s AND Fertig IS NULL AND Ueber IS NULL', $person), ARRAY_A);
    foreach ($top_level as $todo){
        $res .= va_get_todo_parent_option($todo);
    }
    $res .= '</select><br /><select class="va_todo_context_input" style="max-width: 800px;" autocomplete="off">';
    $contexts = $va_xxx->get_col($va_xxx->prepare('SELECT DISTINCT Kontext FROM Todos WHERE Kuerzel=%s ORDER BY Kontext', $person));
    foreach ($contexts as $context){
    	$res .= '<option value="' . ($context? va_only_latin_letters($context): '-1') . '">' . $context . '</option>';
    }
    $res .= '</select><br /><br />';
    
    $res .= '<input type="button" value="Einfügen" id="va_submit_todo_button" />';
    
    return $res;
}

function va_get_todo_parent_option ($todo){
	return '<option value="' . $todo['Id_Todo'] . '" data-context="' . $todo['Kontext'] . '" data-context-simple="' . va_only_latin_letters($todo['Kontext']) . '">' . $todo['Todo'] . '</option>';
}

function va_get_todo_row ($todo){
    $style = $todo['Fertig']? 'background: DarkSeaGreen;' : '';
    
    $classes = [];
    if(!$todo['Ueber']){
        $classes[] = 'va_todo_parent';
    }
    if ($todo['Fertig']){
        $classes[] = 'va_todo_ready';
    }
    
    $res = '<tr class="' . implode(' ', $classes) . '">' . ($todo['Ueber']? '<td class="noCol" style="min-width: 20px"></td><td style="' . $style . '">' : '<td colspan=2>') 
        . $todo['Todo'] . '</td><td style="width: 20px;' . $style . '"><input autocomplete="off" class="va_todo_checkbox" data-id="' 
        . $todo['Id_Todo'] . '" type="checkbox" autocomplete="off" ' . ($todo['Fertig']? 'checked ' : '') . '' . ($todo['Blockiert']? 'disabled ' : '') . '/></tr>';
    return $res;
}
?>