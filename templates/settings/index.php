<div id="app-settings">
	<div id="app-settings-header">
		<button class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
	</div>
	<div id="app-settings-content">
		<form action="">
			<label for="hledger_folder">HLedger Folder</label>
			<input type="text" id="hledger_folder" name="hledger_folder" value="<?php echo($_['hledger_folder']) ?>" />
			<label for="journal_file">Journal File</label>
			<input type="text" id="journal_file" name="journal_file" value="<?php echo($_['journal_file']) ?>" />
			<label for="budget_file">Budget File</label>
			<input type="text" id="budget_file" name="budget_file" value="<?php echo($_['budget_file']) ?>" />
			<input type="submit" value="Save" />
		</form>
	</div>
</div>
