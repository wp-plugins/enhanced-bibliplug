<?php
if (!defined('BIBLIPLUG_DIR'))
{
	die('Invalid access point.');
}

$title = __('Zotero Connector');
$parent_file = 'bibliplug_manager.php';

require_once('class-bibliplug-connection-table.php');

$connection_manager = new Bibliplug_Connection_List_Table();
$connection_manager->prepare_items();

?>
<div class="wrap">
<?php screen_icon('edit'); ?>
<h2><?php echo esc_html( $title ); ?></h2>

<div id="col-container">
	<div id="col-right">
		<div class="col-wrap">
			<h3>Manage Zotero connections</h3>
			<?php $connection_manager->display(); ?>
		</div>
		<!--[if lt ie 9]>
			<p class="description">There's a known bug for sync/delete when using ie8 or ie7. Please use ie9, firefox, or chrome for sync/delete.</p>
		<![endif]-->
		<div id="sync_progress">Syncing...</div>
		<div id="sync_result"></div>
	</div>
	<div id="col-left">
		<div class="col-wrap">
			<div class="form-wrap">
				<h3>Add a Zotero connection</h3>
				<form id="add-zotero-connection" method="post" class="validate" action="<?php echo admin_url("admin.php?page=bibliplug/bibliplug_zotero.php"); ?>">
					<?php wp_nonce_field('bibliplug-zotero-add') ?>
					<div class="form-field form-required">
						<label for="nick-name">Nick Name</label>
						<input name="nick-name" id="nick-name" type="text" size="40" aria-required="true" />
						<p class="description">Nick name of the collection, used as reference category name.</p>
					</div>
					<div class="form-field form-required">
						<label for="account-id">Account ID</label>
						<input name="account-id" id="account-id" type="text" size="40" aria-required="true" />
						<p class="description">The ID of a user or a group.</p>
					</div>
					<div class="form-field form-required">
						<label for="account-type">Account Type</label>
						<select type="text" name="account-type" id="account-type">
							<option value="User" selected="selected" >User</option>
							<option value="Group" >Group</option>
						</select>
					</div>
					<div class="form-field form-required">
						<label for="private-key">Private Key</label>
						<input name="private-key" id="private-key" type="text" size="40" aria-required="true" />
						<p class="description">To obtain the private key, go to the Zotero website > Settings > Feeds/API > choose "Create new private key."</p>
					</div>
					<div class="form-field">
						<label for="collection-name">Collection (optional)</label>
						<input name="collection-name" id="collection-name" type="text" size="40" aria-required="true" />
						<p class="description">Recommended for better synchronization. </p>
					</div>
					<p class="sumbit">
						<input type="submit" class="button" value="Add New Connection" id="submit" name="submit"/>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
