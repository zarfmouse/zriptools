var EventReader = function(url) {this.init(url)};
$.extend(EventReader.prototype, {
    eventHandler: function(data) {},
    boundary: "\n",
    url: undefined,
    stopped: false,
    init: function(url) {
	this.url = url;
    },
    setBoundary: function(b) {
	this.boundary = b;
    },
    setEventHandler: function(f) { 
	this.eventHandler = f; 
    },
    run: function() {
	var start = 0;
	var xhr = new XMLHttpRequest();
	var outer = this;
	xhr.onreadystatechange = function() {
	    if(this.readyState != 3 && this.readyState != 4)
		return;
	    var newStart = this.responseText.indexOf(outer.boundary, start);
	    if(newStart > 0) {
		var length = newStart - start;
		var data = 
		    $.parseJSON(this.responseText.substr(start, length));
		start = newStart+1;
		outer.eventHandler(data);
	    }
	    if(this.readyState == 4 && !outer.stopped) {
		outer.run();
	    }
	}
	xhr.open("GET",this.url, true);
	xhr.send();
    },
    stop: function() {
	this.stopped = true;
    }
});

var events = new EventReader('task-status.php');
events.setEventHandler(function(data) { 
    var sorted_keys = Object.keys(data).sort();
    $.each(sorted_keys, function(index, key) {
	var value = data[key];
	var id = key.replace(/[^a-z0-9A-Z]/g, '_');
	var e = $('#'+id);
	if(e.length == 0) {
	    $("#tasks").append('<li id="'+id+'"><div class="key">'+key+'</div><div class="message"></div><div class="progress"></div></li>');
	    e = $('#'+id);
	}
	$('#'+id+' .message').html(value.message);
	$('#'+id+' .progress').progressbar({value: value.percent});
    });
});
events.run();
