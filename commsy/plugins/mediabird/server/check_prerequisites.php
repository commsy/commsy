<?php 
class MediabirdValidator {
	function perform($config_path) {
	/*
	todo:
	* check ghostscript capabilities of convert
	* render formula for test purpose
	* check pages of given pdf file
	*/
		
		$result = true;
?>
<table>
	<tr>
		<th>Check</th>
		<th>Status</th>
		<th>Hint</th>
	</tr>
	<tr>
		<?php
		$result = $result && file_exists($config_path);
		?>
		<td>Configuration present</td>
		<td class="<?php echo $result ? 'green' : 'red'; ?>"><?php echo $result ? "Yes":"No"; ?></td>
		<td>
		<?php if(!$result): ?>
		Run <a href="../setup/index.php">setup</a> first.
		<?php endif; ?>
		</td>
	</tr>
	<?php if ($result): ?>
	<tr>
		<?php
		include_once($config_path);
		$result = (!empty(MediabirdConfig::$pdftk_path) && MediabirdUtility::execExists(MediabirdConfig::$pdftk_path)) || (!empty(MediabirdConfig::$pdfinfo_path) && MediabirdUtility::execExists(MediabirdConfig::$pdfinfo_path));
		?>
		<td>PdfTk or pdfinfo accessible</td>
		<td class="<?php echo $result ? 'green' : 'red'; ?>"><?php echo $result ? "Yes":"No"; ?></td>
		<td>
		<?php if(!$result): ?>
		Install pdftk or pdfinfo. You might have to specify the path to pdftk or pdfinfo in the module options or config file.
		<?php endif; ?>
		</td>
	</tr>
	<tr>
		<?php
		$result = MediabirdUtility::execExists(MediabirdConfig::$latex_path);
		?>
		<td>LaTeX accessible</td>
		<td class="<?php echo $result ? 'green' : 'red'; ?>"><?php echo $result ? "Yes":"No"; ?></td>
		<td>
		<?php if(!$result): ?>
		Install LaTeX and specify the path to the latex executable in the module options or config file.
		<?php endif; ?>
		</td>
	</tr>
	<tr>
		<?php
		$result = MediabirdUtility::execExists(MediabirdConfig::$convert_path);
		?>
		<td>LaTeX helper DVIPNG accessible</td>
		<td class="<?php echo $result ? 'green' : 'red'; ?>"><?php echo $result ? "Yes":"No"; ?></td>
		<td>
		<?php if(!$result): ?>
		Install dvipng and specify the path to the dvipng executable in the module options or config file.
		<?php endif; ?>
		</td>
	</tr>
	<tr>
		<?php
		$result = MediabirdUtility::execExists(MediabirdConfig::$magic_path);
		?>
		<td>ImageMagick accessible</td>
		<td class="<?php echo $result ? 'green' : 'red'; ?>"><?php echo $result ? "Yes":"No"; ?></td>
		<td>
		<?php if(!$result): ?>
		Install ImageMagick and specify the path to the convert executable in the module options or config file.
		<?php endif; ?>
		</td>
	</tr>
	<?php endif; ?>
</table><?php
	}	
}
?>
