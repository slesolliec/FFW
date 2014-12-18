function happy_selector_onchange(id) {

	if ( $('#'+id+'_select').val() == 'Autre ...' ) {
		$('#'+id+'_input').css('display','inline');
		$('#'+id+'_input').attr('disabled','');
	} else {
		$('#'+id+'_input').css('display','none');
		$('#'+id+'_input').attr('disabled','disabled');
	}
	
}

function select_filter() {
	select_id = this.id.slice(14);
	options = $('#'+select_id+' option');
	for(i=0;i<options.length;i++) {
		if (options[i].innerHTML.indexOf(this.value) == -1) {
			options[i].style.display = 'none';
		} else {
			options[i].style.display = 'block';
		}
	}
//	alert(options[0].innerHTML);
}