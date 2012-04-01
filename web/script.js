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
	console.log("stop");
	this.stopped = true;
    }
});

var events = new EventReader('task-status.php');
events.setEventHandler(function(data) { console.log(data); });
events.run();

