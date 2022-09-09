$('.usergroup_apply').hide();
$('.usergroup_apply_change').hide();
$('.usergroup_apply_revoke').hide();

if (value == 25) {
	$('.usergroup_apply, .affectedSetting').show();
	$('#receiverAffected').show();
}
if (value == 27) {
	$('.usergroup_apply_change, .affectedSetting').show();
	$('#receiverAffected').show();
}
if (value == 28) {
	$('.usergroup_apply_revoke, .affectedSetting').show();
	$('#receiverAffected').show();
}
