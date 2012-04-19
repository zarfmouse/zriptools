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

var RipAudio = function(id) {this.init(id);};
$.extend(RipAudio.prototype, {
    element: undefined,
    init: function(id) {
	this.element = $('<li class="task"><div class="cell key">'+id+'</div><div class="cell type">RipAudio</div><div class="cell meta"></div><div class="cell progress_container"><div class="message"></div><div class="progress"></div></div></li>');
	this.addBarCode();
	this.addCDDB();
	this.addMusicBrainz();
    },
    getId: function() {
	return this.element.find('.key').html();
    },
    setActive: function(active) {
	if(active) {
	    this.element.addClass('active');
	} else {
	    this.element.removeClass('active');
	    this.setMessage("Complete");
	}
    },
    setMessage: function(message) {
	this.element.find('.message').html(message);
    },
    setProgress: function(percent) {
	this.element.find('.progress').progressbar({value: percent});
    },
    insert: function(root) {
	$(root).append(this.element);
    },
    appendMeta: function(meta) {
	this.element.find('.meta').append(meta);
    },
    addBarCode: function() {
	var wrapper = $('<div class="barcode"></div>');
	var input = $('<input type="text" name="barcode" value=""/>');
	wrapper.append(input);
	this.appendMeta(wrapper);
	var obj = this;
	input.change(function (ev) {
	    console.log('changed barcode on '+obj.getId()+' to '+$(this).val());
	});
    },
    addCDDB: function() {
	var wrapper = $('<div class="cddb"></div>');
	var select = $('<select name="cddb"><option>Loading...</option><option></option></select>');
	wrapper.append(select);
	this.appendMeta(wrapper);
	var obj = this;
	select.change(function (ev) {
	    console.log('changed cddb on '+obj.getId()+' to '+$(this).val());
	});
    },
    addMusicBrainz: function() {
	var wrapper = $('<div class="musicbrainz"></div>');
	var select = $('<select name="musicbrainz"><option>Loading...</option><option></option></select>');
	wrapper.append(select);
	this.appendMeta(wrapper);
	var obj = this;
	select.change(function (ev) {
	    console.log('changed musicbrainz on '+obj.getId()+' to '+$(this).val());
	});
    },
});

$.extend(RipAudio, {
    cache: {},
    root: '#tasks',
    find: function(id) {
	var retval = this.cache[id];
	return retval;
    },
    find_or_create: function(id) { 
	var retval = this.find(id);
	if(retval === undefined) {
	    retval = new this(id);
	    retval.insert(this.root);
	    this.cache[id] = retval;
	} 
	return this.find(id);
    },
    update_from_events: function(data) {
	var saw = {};
	var sorted_keys = Object.keys(data).sort();
	var obj = this;
	$.each(sorted_keys, function(index, key) {
	    var value = data[key];
	    // TODO: Refactor to allow multiple types using value.type.
	    var task = obj.find_or_create(key);
 	    saw[key] = true;
	    task.setProgress(value.percent);
	    task.setMessage(value.message);
	    task.setActive(value.message != 'Complete');
	});
	$(".task.active").each(function(i,e) {
	    var id = $(e).find('.key').html();
	    var task = obj.find(id);
	    $(e).setActive(saw[id] === true);
	});
    },
});

$.getJSON('unresolved-tasks.php', function(data) {
    RipAudio.update_from_events(data);
    var events = new EventReader('task-status.php');
    events.setEventHandler(RipAudio.update_from_events); 
    events.run();
});

