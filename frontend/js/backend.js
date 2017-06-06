var backend = '../backend/backend.php';

// ok error alert ajax_error
function log(op,type,message,name=null) {
	debug = 1;
	line = op + ":" + type + ':' + message;
	if(!name) {
		//name = editableGrid.name;
		name = self.name;
	}
	console.log(line);
	if(type == 'alert') {
		window.alert('line');
		type = 'error';
	}
	if(type == 'ajax_error') {
		line = 'There was an error communicating with the system';
		type='error';
	}
	if(type == 'error') {
		show_message(name,line);
	}
}


function show_message(table,msg){
	var self = this;
	//console.log('message:', msg);
	var msgId = table+'_message';
	var x = $('#'+msgId);
	x.html(msg);
	x.fadeTo("normal", 0.5, function() {
			x.fadeTo(5000, 1, function() {
			x.html('');
			});
	});
};


// highlightRow and highlight are used to show a visual feedback. If the row has been successfully modified, it will be highlighted in green. Otherwise, in red
function highlightRow(rowId, bgColor, after) {
	var rowSelector = $("#" + rowId);
	rowSelector.css("background-color", bgColor);
	rowSelector.fadeTo("normal", 0.5, function() {
		rowSelector.fadeTo("fast", 1, function() {
			rowSelector.css("background-color", '');
		});
	});
}

function highlight(div_id, style) {
	highlightRow(div_id, style == "error" ? "#e5afaf" : style == "warning" ? "#ffcc00" : "#8dc70a");
}



function updateCellValue(editableGrid, rowIndex, columnIndex, oldValue, newValue, row, onResponse) {
	$.ajax({
		url: editableGrid.getActionUrl('update'),
		type: 'POST',
		dataType: "html",
		data: {
			tablename: editableGrid.name,
			id: editableGrid.getRowId(rowIndex),
			newvalue: editableGrid.getColumnType(columnIndex) == "boolean" ? (newValue ? 1 : 0) : newValue,
			colname: editableGrid.getColumnName(columnIndex),
			coltype: editableGrid.getColumnType(columnIndex),
			row: editableGrid.getRowValues(rowIndex),
		},
		success: function(response) {
			//log('response:',response);
			// reset old value if failed then highlight row
			var success = onResponse ? onResponse(response) : ((response.indexOf("error")<0) || !isNaN(parseInt(response))); // by default, a sucessfull reponse can be "ok" or a database id 
			highlight(row.id, success ? "ok" : "error");
			if (!success){
				editableGrid.setValueAt(rowIndex, columnIndex, oldValue);
				var res = jQuery.parseJSON(response);
				log('update','error', res.error, editableGrid.name);
				return;
			} 
			log('update','ok','updated' + row.id + response);
			//show_message(editableGrid.name, response);
		},
		error: function(XMLHttpRequest, textStatus, exception) {
			//alert("Ajax failure\n" + errortext);
			log('update','error',exception);
			//show_message(editableGrid.name, exception)
			//console.log('error:', exception);
		},
		async: true
	});
}



function DatabaseGrid(table,profile) {
	var that = this;
	this.profile = profile;
	this.editableGrid = new EditableGrid(table, {
		enableSort: true,
		pageSize: 10,
		// Once the table is displayed, we update the paginator state
		tableRendered: function() {
			updatePaginator(this, table + '_paginator');
		},
		tableLoaded: function() {
			that.initializeGrid(this, table);
		},
		modelChanged: function(rowIndex, columnIndex, oldValue, newValue, row) {
			updateCellValue(this, rowIndex, columnIndex, oldValue, newValue, row);
		},
		getActionUrl: function(action){
			var self = this;
			var profile = this.profile;
			var url= backend + '?action='+action+'&profile='+profile+'&table='+self.name;
			// log('DatabaseGrid','ok','Grid calling:' +url);
			return url;
		},
		loadJSON: function(url, callback, dataOnly){
			this.lastURL = url; 
			var self = this;
			// should never happen
			if (!window.XMLHttpRequest) {
				log('loadJSON','alert',"Cannot load a JSON url with this browser!"); 
				return false;
			}
			var ajaxRequest = new XMLHttpRequest();
			ajaxRequest.onreadystatechange = function () {
				if (this.readyState == 4) {
					if (!this.responseText) { 
						var error = "Could not load JSON from url '" + url + "'";
						log('loadJSON','error', error, self.name);
						return false; 
					}
					try {
						self.processJSON(this.responseText);
					}
					catch (err) {
						var error = "Invalid JSON data obtained from url '" + url + "':" + err;
						//console.error("Invalid JSON data obtained from url '" + url + "'"); 
						log('loadJSON','ajax_error', error, self.name);
						return false; 
					}
					log('loadJSON','ok','Loaded '+self.name,self.name);
					self._callback('json', callback);
				}
			};
			ajaxRequest.open("GET", this._addUrlParameters(url, dataOnly), true);
			ajaxRequest.send("");
			return true;
		},

		dateFormat: 'EU', // date must be always dd/mm/yyyy
		
		checkDate: function(strDate, strDatestyle) {
			//console.log('strDate:', strDate, 'strDatestyle:', strDatestyle);
			strDatestyle = strDate;
			if(strDate && strDate !== ""){
				var arr = strDate.split('/');
				if(arr && arr.length ==3){
					
					var date = new Date(arr[2],arr[1]-1,arr[0],12,0,0);
					var date2 = new Date(strDate);
					//console.log('date:', arr, date2===date);
					if(date && date.getFullYear() > 1970)
						strDatestyle = date.toISOString().substr(0,10);
					
				}
				
			}
			return { 
					formattedDate: strDatestyle,
					sortDate: strDate,
					dbDate: strDatestyle 
			};
	}
        

	});
	this.fetchGrid(table,profile);
}


DatabaseGrid.prototype.fetchGrid = function(table,config) {
	url = backend + "?action=load&profile="+config+ "&table=" + table;
	var that = this;
	this.editableGrid.loadJSON(url);
};



DatabaseGrid.prototype.initializeGrid = function(grid,table) {
	
	var self = this;
	console.log('grid:', grid, ' vs this:', self);
	
	//self.config = config;
	// render for the action column
	grid.setCellRenderer("action", new CellRenderer({
		render: function(cell, id) {
			// this action will remove the row, so first find the ID of the row containing this cell 
			var rowId = self.editableGrid.getRowId(cell.rowIndex);
			//console.log('table:', table, 'rowId:',rowId, 'cellId:', cell.rowIndex);
			
			
			
			cell.innerHTML = "<a href='#' class='delete-row' data-table='"+table+"' data-id='"+id+"'><i class='fa fa-trash-o red' ></i></a>";
			cell.innerHTML += "  <a href='#' class='copy-row' data-table='"+table+"' data-id='"+id+"'  ><i class='fa fa-copy' ></i></a>";

		}
	}));

	
function get_table_id(ele) {
		var id = ele.closest('tr').prop('id');
		var x = id.split('_');
		id = x.pop();
		var table = x.join('_');
		return {
			id: id,
			table: table
		};
	}
	/*
	$('body').on('click', '.delete-row', function(e) {
		console.log('delete row id: ');
		var xdata = get_table_id($(this));
		e.stopImmediatePropagation();
		console.log('delete row id: ',  xdata, self.editableGrid.name);
		if (xdata.table == self.editableGrid.name)
			self.deleteRow(xdata.id);
	});
			

	$('body').on('click', '.copy-row', function(e) {
		//console.log(this, 'copy row vs', e.target);
		console.log('copy row id: ');
		e.preventDefault();
		e.stopImmediatePropagation();
		var xdata = get_table_id($(this));
		console.log('duplicateRow id: ', $(this).data('id'), xdata, self.editableGrid.name);
		if (xdata.table == self.editableGrid.name){
			self.duplicateRow(xdata.id);
		}
		return false;
	});
	*/
	var actionEle = '#'+table +' .editablegrid-action a';
	$('body').off('click', actionEle).on('click', actionEle, function(e) {
	//$('body').one('click', actionEle, function(e){
		var $this = $(this);
		e.preventDefault();
		e.stopPropagation();
		if($this.data('table') == self.editableGrid.name){
			var id = $this.data('id');
			//console.log('copy row editablegrid-action: ', id, self.editableGrid.name, this, e.target);
			if($this.hasClass('delete-row')){
				self.deleteRow(id);
			}else{
				self.duplicateRow(id);
			}
		}
		
	});


	//console.log('table is: ' + table);
	//	grid.renderGrid('demo',"testgrid");
	// add
	$(".grid_addbutton").click(function(e) {
		if ($(this).prop('id').indexOf(self.editableGrid.name) >= 0) {
			//console.log('click on add');
			self.addRow();
		};
	});

	//filter
	$(".grid_filter").keyup(function(e) {
		if ($(e.target).prop('name').indexOf(self.editableGrid.name) >= 0) {
			//console.log('grid_filter:', e.target);
			self.editableGrid.filter($(this).val());
		}
		// To filter on some columns, you can set an array of column index 
		//datagrid.editableGrid.filter( $(this).val(), [0,3,5]);
	});
	grid.renderGrid(table,'excel testgrid');
};




DatabaseGrid.prototype.deleteRow = function(id) {
	var self = this;
	if (1 || confirm('Are you sure you want to delete the row id ' + id)) {
		$.ajax({
			url: self.editableGrid.getActionUrl('delete'),
			type: 'POST',
			dataType: "html",
			data: {
				tablename: self.editableGrid.name,
				id: id,
				row: self.editableGrid.getRowValues(self.editableGrid.getRowIndex(id)),
			},
			success: function(response) {
				if (response == "ok"){
					self.editableGrid.removeRow(id);
					log('deleteRow','ok', "RowId:"+id +" has been deleted", self.editableGrid.name);
				}
			},
			error: function(XMLHttpRequest, textStatus, exception) {
				//alert("Ajax failure\n" + errortext);
				//show_message(self.editableGrid.name,errortext);
				log('deleteRow','error', errortext, self.editableGrid.name);
			},
			async: true
		});
	}
};

DatabaseGrid.prototype.duplicateRow = function(id) {
	var self = this;
	$.ajax({
		url: self.editableGrid.getActionUrl('duplicate'),
		type: 'POST',
		dataType: "html",
		data: {
			table: self.editableGrid.name,
			id: id,
			row: self.editableGrid.getRowValues(self.editableGrid.getRowIndex(id)),
		},
		success: function(response) {
			if (response && response.indexOf('error') <0) {
				show_message(self.editableGrid.name,"Row duplicated"); //todo put in log
				self.fetchGrid(self.editableGrid.name, self.editableGrid.profile);

				// goto last page
				self.editableGrid.lastPage();
				log('duplicateRow','ok', "RowId:"+id +" has been duplicated!", self.editableGrid.name);
			}else{

				log('duplicateRow','error', response, self.editableGrid.name);
			}
		},
		error: function(XMLHttpRequest, textStatus, exception) {
			//alert("Ajax failure\n" + errortext);
			//show_message(self.editableGrid.name,errortext);
			log('duplicateRow','error', errortext, self.editableGrid.name);
		},
		async: true
	});
};

DatabaseGrid.prototype.addRow = function(id) {
	var self = this;
	$.ajax({
		url: self.editableGrid.getActionUrl('add'),
		type: 'POST',
		dataType: "html",
		data: {
			tablename: self.editableGrid.name,
		},
		success: function(response) {
			if (response.indexOf("error") < 0) {
				log('addRow','ok', "Row:" + id + " has been added");
				self.fetchGrid(self.editableGrid.name, self.editableGrid.profile);
			}
			else{
				log('addRow','error', response);
		  }
		},
		error: function(XMLHttpRequest, textStatus, exception) {
			//alert("Ajax failure\n" + errortext);
			//show_message(self.editableGrid.name,errortext);
			log('addRow','ajax_error', exception, self.editableGrid.name);
		},
		async: true
	});
};

function updatePaginator(grid, divId) {
	divId = divId || "paginator";
	var paginator = $("#" + divId).empty();
	var nbPages = grid.getPageCount();

	// get interval
	var interval = grid.getSlidingPageInterval(20);
	if (interval == null) return;

	// get pages in interval (with links except for the current page)
	var pages = grid.getPagesInInterval(interval, function(pageIndex, isCurrent) {
		if (isCurrent) return "<span id='currentpageindex'>" + (pageIndex + 1) + "</span>";
		return $("<a>").css("cursor", "pointer").html(pageIndex + 1).click(function(event) {
			grid.setPageIndex(parseInt($(this).html()) - 1);
		});
	});

	// "first" link
	var link = $("<a class='nobg'>").html("<i class='fa fa-fast-backward'></i>");
	if (!grid.canGoBack()) link.css({
			opacity: 0.4,
			filter: "alpha(opacity=40)"
		});
	else link.css("cursor", "pointer").click(function(event) {
			grid.firstPage();
		});
	paginator.append(link);

	// "prev" link
	link = $("<a class='nobg'>").html("<i class='fa fa-backward'></i>");
	if (!grid.canGoBack()) link.css({
			opacity: 0.4,
			filter: "alpha(opacity=40)"
		});
	else link.css("cursor", "pointer").click(function(event) {
			grid.prevPage();
		});
	paginator.append(link);

	// pages
	for (p = 0; p < pages.length; p++) paginator.append(pages[p]).append(" ");

	// "next" link
	link = $("<a class='nobg'>").html("<i class='fa fa-forward'>");
	if (!grid.canGoForward()) link.css({
			opacity: 0.4,
			filter: "alpha(opacity=40)"
		});
	else link.css("cursor", "pointer").click(function(event) {
			grid.nextPage();
		});
	paginator.append(link);

	// "last" link
	link = $("<a class='nobg'>").html("<i class='fa fa-fast-forward'>");
	if (!grid.canGoForward()) link.css({
			opacity: 0.4,
			filter: "alpha(opacity=40)"
		});
	else link.css("cursor", "pointer").click(function(event) {
			grid.lastPage();
		});
	paginator.append(link);
};

function showAddForm() {
	if ($("#addform").is(':visible'))
		$("#addform").hide();
	else
		$("#addform").show();
}
