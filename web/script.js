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

var SavableWidget = function(task, action) {this.init(task, action)};
$.extend(SavableWidget.prototype, {
    wrapper: null,
    task: null,
    trigger: null,
    action: null,
    init: function(task, action) {
	var widget = this;
	widget.task = task;
	widget.action = action;
	widget.wrapper = $('<div></div>').attr('class', action);
	widget.trigger = $('<div class="trigger"></div>');
	widget.wrapper.append(widget.trigger);
	task.appendMeta(widget.wrapper);
	$(widget.wrapper).on('click', '.trigger.edit', function(ev) {
	    widget.displayWrite(true);
	});
	widget.displayWrite();
    },
    editTrigger: function() {
	var widget = this;
	widget.trigger.off('click');
	widget.trigger.removeClass('accept wait edit').addClass('edit').attr('title', 'Click to edit.');
    },
    waitTrigger: function() {
	var widget = this;
	widget.trigger.off('click');
	widget.trigger.removeClass('accept wait edit').addClass('wait').attr('title', 'Loading...');
    },
    acceptTrigger: function() {
	var widget = this;
	widget.trigger.off('click');
	widget.trigger.removeClass('accept wait edit').addClass('accept').attr('title', 'Click to submit.');
    },
});

var SelectWidget = function(task, action) {this.init(task, action)};
$.extend(SelectWidget.prototype, SavableWidget.prototype, {
    init: function(task, action) {
	SavableWidget.prototype.init.call(this, task, action);
    },
    displayRead: function(data) {
	var widget = this;
	var div = $('<div class="chosen"></div>').text(data.options[data.chosen]);
	widget.wrapper.find('select').remove();
	widget.wrapper.prepend(div);
	widget.wrapper.attr('title', data.chosen);
	widget.editTrigger();
    },
    displayWrite: function(force) {
	var widget = this;
	widget.waitTrigger();
	var select = $('<select name="data"></select>');
	widget.wrapper.find('.chosen').remove();
	
	var current_ajax = null;
	var load_options = function() { 
	    if(current_ajax !== null) {
		current_ajax.abort();
	    }
	    current_ajax = $.ajax({
		url: 'rip_audio_ajax.php/'+widget.action+'/'+widget.task.getId(),
		type: 'GET',
		dataType: 'json',
		success: function(data) {
		    if(data.chosen !== null && !force) {
			widget.displayRead(data);
		    } else {
			$.each(data.options, function(i,o) {
			    var option = $('<option></option>').text(o);
			    option.attr('value', i);
			    if(data.chosen === i) {
				option.attr('selected', 'selected');
			    }
			    select.append(option);
			});
			var change_function = function (ev) {
			    widget.waitTrigger();
			    $.ajax({
				url: 'rip_audio_ajax.php/'+widget.action+'/'+widget.task.getId(),
				type: 'POST',
				dataType: 'json',
				data: {data: select.val()},
				success: function(data) {
				    widget.displayRead(data);
				}
			    });
			};
			select.change(change_function);
			widget.acceptTrigger();
			widget.trigger.one('click', change_function);
			widget.wrapper.prepend(select);
		    }
		}
	    });
	};
	load_options();
	widget.trigger.on('click', load_options);
    },
});

var CDDBWidget = function(task) {this.init(task)};
$.extend(CDDBWidget.prototype, SelectWidget.prototype, {
    init: function(task) {
	SelectWidget.prototype.init.call(this, task, 'cddb');
    }
});

var MusicbrainzWidget = function(task) {this.init(task)};
$.extend(MusicbrainzWidget.prototype, SelectWidget.prototype, {
    init: function(task) {
	SelectWidget.prototype.init.call(this, task, 'musicbrainz');
    }
});

var TextWidget = function(task, action) {this.init(task, action, size)};
$.extend(TextWidget.prototype, SavableWidget.prototype, {
    size: null,
    init: function(task, action, size) {
	SavableWidget.prototype.init.call(this, task, action);
	this.size= size;
    },
    displayRead: function(data) {
	var widget = this;
	var text = data.chosen;
	if(text == '') 
	    text = "N/A";
	var div = $('<div class="chosen"></div>').text(text);
	widget.wrapper.find('input').remove();
	widget.wrapper.prepend(div);
	widget.wrapper.attr('title', data.chosen);
	widget.editTrigger();
    },
    displayWrite: function(force) {
	var widget = this;
	widget.waitTrigger();
	var input = $('<input name="data"></input>');
	if(this.size !== undefined)
	    input.attr('size', this.size);
	widget.wrapper.find('.chosen').remove();
	
	var current_ajax = null;
	var load_options = function() { 
	    if(current_ajax !== null) {
		current_ajax.abort();
	    }
	    current_ajax = $.ajax({
		url: 'rip_audio_ajax.php/'+widget.action+'/'+widget.task.getId(),
		type: 'GET',
		dataType: 'json',
		success: function(data) {
		    if(data.chosen !== null && !force) {
			widget.displayRead(data);
		    } else {
			input.val(data.chosen);
			var change_function = function (ev) {
			    widget.waitTrigger();
			    $.ajax({
				url: 'rip_audio_ajax.php/'+widget.action+'/'+widget.task.getId(),
				type: 'POST',
				dataType: 'json',
				data: {data: input.val()},
				success: function(data) {
				    widget.displayRead(data);
				}
			    });
			};
			input.change(change_function);
			widget.acceptTrigger();
			widget.trigger.one('click', change_function);
			widget.wrapper.prepend(input);
		    }
		}
	    });
	};
	load_options();
	widget.trigger.on('click', load_options);
    },
});

var NoteWidget = function(task) {this.init(task)};
$.extend(NoteWidget.prototype, TextWidget.prototype, {
    init: function(task) {
	TextWidget.prototype.init.call(this, task, 'note');
    }
});

var SlotWidget = function(task) {this.init(task)};
$.extend(SlotWidget.prototype, TextWidget.prototype, {
    init: function(task) {
	TextWidget.prototype.init.call(this, task, 'slot', 8);
    }
});

var BarcodeWidget = function(task) {this.init(task)};
$.extend(BarcodeWidget.prototype, TextWidget.prototype, {
    init: function(task) {
	TextWidget.prototype.init.call(this, task, 'barcode', 13);
    }
});


var RipAudio = function(id) {this.init(id);};
$.extend(RipAudio.prototype, {
    element: undefined,
    init: function(id) {
	this.element = $('<li class="task"><div class="cell key">'+id+'</div><div class="cell type">RipAudio</div><div class="cell meta"></div><div class="cell progress_container"><div class="message"></div><div class="progress"></div></div><div class="trigger_container"><div class="trigger delete"></div></div></li>');
	new SlotWidget(this);
	new BarcodeWidget(this);
	new CDDBWidget(this);
	new MusicbrainzWidget(this);
	new NoteWidget(this);
    },
    getId: function() {
	return this.element.find('.key').html();
    },
    setActive: function(active) {
	if(active) {
	    this.element.addClass('active');
	} else {
	    this.element.removeClass('active');
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
	$.each(sorted_keys, function(index, key) {
	    var value = data[key];
	    var task = RipAudio.find_or_create(key);
 	    saw[key] = true;
	    task.setProgress(value.percent);
	    task.setMessage(value.message);
	    task.setActive(value.message != 'Complete' && value.message != 'Success' && value.message != 'Failure');
	});
	$(".task.active").each(function(i,e) {
	    var id = $(e).find('.key').html();
	    var task = RipAudio.find(id);
	    task.setActive(saw[id] === true);
	});
    },
});

$(document).ready(function() {
    $.getJSON('unresolved-tasks.php', function(data) {
	RipAudio.update_from_events(data);
	var events = new EventReader('task-status.php');
	events.setEventHandler(RipAudio.update_from_events); 
	events.run();
    });
});


