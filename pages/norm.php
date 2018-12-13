<?php
function va_norm_page (){
	global $va_xxx;
	
	?><div class="entry-content"><?php
	
	if (isset($_REQUEST['id'])){
		
		$entry = $va_xxx->get_row($va_xxx->prepare('SELECT id, orth, gender, lang_family, pos, affix FROM norm.m_entries WHERE id = %d', $_REQUEST['id']), ARRAY_A);
		?>
		<table style="max-width: 600px;">
			<tbody>
				<tr>
					<td>Id</td>
					<td><?php echo $entry['id'];?></td>
				</tr>
				<tr>
					<td>Orthography</td>
					<td><?php echo $entry['orth'];?></td>
				</tr>
				<tr>
					<td>Part of speech</td>
					<td><?php echo $entry['pos'];?></td>
				</tr>
				<tr>
					<td>Gender</td>
					<td><?php echo $entry['gender'];?></td>
				</tr>
				<tr>
					<td>Language Family</td>
					<td><?php echo $entry['lang_family'];?></td>
				</tr>
				<tr>
					<td>Affix</td>
					<td><?php echo $entry['affix'];?></td>
				</tr>
			</tbody>
		</table>
		
		<br />
		<br />
		
		<a href="<?php echo remove_query_arg('id', get_permalink());?>">Back to list</a>
		<?php
	}
	else {
		?>
		
		<h1>Morpho-lexical types</h1>

		<ul>
		<?php
		$entries = $va_xxx->get_results('SELECT id, orth, gender, lang_family, pos, affix FROM norm.m_entries', ARRAY_A);
		foreach ($entries as $entry){
			echo '<li><a href="' . add_query_arg('id', $entry['id'], get_permalink()) . '">' . va_format_lex_type($entry['orth'], $entry['lang_family'], $entry['pos'], $entry['gender'], $entry['affix']) . '</a></li>';
		}
		?>
		</ul>
		<?php
	}
	
	?></div><?php
}
?>