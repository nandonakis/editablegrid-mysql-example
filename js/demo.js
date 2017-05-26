/**
 *  highlightRow and highlight are used to show a visual feedback. If the row has been successfully modified, it will be highlighted in green. Otherwise, in red
 */
function highlightRow(rowId, bgColor, after)
{
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
        
/**
   updateCellValue calls the PHP script that will update the database. 
 */
function updateCellValue(editableGrid, rowIndex, columnIndex, oldValue, newValue, row, onResponse)
{      
	$.ajax({
		url: 'backend.php?action=update',
		type: 'POST',
		dataType: "html",
	   		data: {
			tablename : editableGrid.name,
			id: editableGrid.getRowId(rowIndex), 
			newvalue: editableGrid.getColumnType(columnIndex) == "boolean" ? (newValue ? 1 : 0) : newValue, 
			colname: editableGrid.getColumnName(columnIndex),
			coltype: editableGrid.getColumnType(columnIndex)			
		},
		success: function (response) 
		{ 
			// reset old value if failed then highlight row
			var success = onResponse ? onResponse(response) : (response == "ok" || !isNaN(parseInt(response))); // by default, a sucessfull reponse can be "ok" or a database id 
			if (!success) editableGrid.setValueAt(rowIndex, columnIndex, oldValue);
		    highlight(row.id, success ? "ok" : "error"); 
		},
		error: function(XMLHttpRequest, textStatus, exception) { alert("Ajax failure\n" + errortext); },
		async: true
	});
   
}
   
function DatabaseGrid(table) 
{ 
	this.editableGrid = new EditableGrid(table, {
		enableSort: true,
	    // define the number of row visible by page
      	pageSize: 50,
      // Once the table is displayed, we update the paginator state
        tableRendered:  function() {  updatePaginator(this); },
   	    tableLoaded: function() { datagrid.initializeGrid(this,table); },
				modelChanged: function(rowIndex, columnIndex, oldValue, newValue, row) {
   	   	updateCellValue(this, rowIndex, columnIndex, oldValue, newValue, row);
       	}
 	});
	this.fetchGrid(table); 
	
}

DatabaseGrid.prototype.fetchGrid = function(table)  {
	// call a PHP script to get the data
	this.editableGrid.loadJSON("backend.php?db_tablename="+table);
};

DatabaseGrid.prototype.initializeGrid = function(grid,table) {

  var self = this;

// render for the action column
	grid.setCellRenderer("action", new CellRenderer({ 
		render: function(cell, id) {                 
	      cell.innerHTML+= "<a href='#' onclick=\"datagrid.deleteRow("+id+");\"><i  class='fa fa-trash-o red' ></i></a>";
        cell.innerHTML+= "  <a href='#' onclick=\"datagrid.duplicateRow("+id+");\"><i  class='fa fa-copy' ></i></a>";
		}
	})); 
  console.log('table is: '+ table);
//	grid.renderGrid('demo',"testgrid");
 	grid.renderGrid(table,"testgrid");
};    

DatabaseGrid.prototype.deleteRow = function(id) 
{
  var self = this;

  if ( confirm('Are you sur you want to delete the row id ' + id )  ) {

        $.ajax({
		url: 'backend.php?action=delete',
		type: 'POST',
		dataType: "html",
		data: {
			tablename : self.editableGrid.name,
			id: id 
		},
		success: function (response) 
		{ 
			if (response == "ok" )
		        self.editableGrid.removeRow(id);
		},
		error: function(XMLHttpRequest, textStatus, exception) { alert("Ajax failure\n" + errortext); },
		async: true
	});

        
  }
			
};
DatabaseGrid.prototype.duplicateRow = function(id) 
{

  var self = this;

  
    $.ajax({
    url: 'backend.php?action=duplicate',
    type: 'POST',
    dataType: "html",
    data: {
        tablename : self.editableGrid.name,
        id: id 
    },
    success: function (response) 
    { 
        if (response == "ok" ){
            
            alert("Row duplicated : reload model");
            self.fetchGrid(self.editableGrid.name);
        }
            
    },
    error: function(XMLHttpRequest, textStatus, exception) { alert("Ajax failure\n" + errortext); },
    async: true
});

    
  
			
};  


DatabaseGrid.prototype.addRow = function(id) 
{

  var self = this;
    $.ajax({
    url: 'backend.php?action=add',
    type: 'POST',
    dataType: "html",
    data: {
        tablename: self.editableGrid.name,
    },
    success: function (response) 
    { 
        if (response.indexOf("error")<0 ) {
            var row = jQuery.parseJSON(response);
            // hide form
            //showAddForm();   
            //form.find("input[type=text]").val("");
            var id = row.id;
            alert("Row added : reload model:"+id);
            //self.fetchGrid(self.editableGrid.name);
            var rowIndex = self.editableGrid.pageSize < 0?0:self.editableGrid.pageSize-1;
            //var row = self.editableGrid.getRowValues(rowIndex);
            //row.id = id;
            console.log('rowCount:', rowIndex, ' row:', row );
            self.editableGrid.insertAfter(rowIndex-1, id, row);
        }
        else 
          alert("error");
    },
    error: function(XMLHttpRequest, textStatus, exception) { alert("Ajax failure\n" + errortext); },
    async: true
});

        
			
}; 




function updatePaginator(grid, divId)
{
    divId = divId || "paginator";
	var paginator = $("#" + divId).empty();
	var nbPages = grid.getPageCount();

	// get interval
	var interval = grid.getSlidingPageInterval(20);
	if (interval == null) return;
	
	// get pages in interval (with links except for the current page)
	var pages = grid.getPagesInInterval(interval, function(pageIndex, isCurrent) {
		if (isCurrent) return "<span id='currentpageindex'>" + (pageIndex + 1)  +"</span>";
		return $("<a>").css("cursor", "pointer").html(pageIndex + 1).click(function(event) { grid.setPageIndex(parseInt($(this).html()) - 1); });
	});
		
	// "first" link
	var link = $("<a class='nobg'>").html("<i class='fa fa-fast-backward'></i>");
	if (!grid.canGoBack()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { grid.firstPage(); });
	paginator.append(link);

	// "prev" link
	link = $("<a class='nobg'>").html("<i class='fa fa-backward'></i>");
	if (!grid.canGoBack()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { grid.prevPage(); });
	paginator.append(link);

	// pages
	for (p = 0; p < pages.length; p++) paginator.append(pages[p]).append(" ");
	
	// "next" link
	link = $("<a class='nobg'>").html("<i class='fa fa-forward'>");
	if (!grid.canGoForward()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { grid.nextPage(); });
	paginator.append(link);

	// "last" link
	link = $("<a class='nobg'>").html("<i class='fa fa-fast-forward'>");
	if (!grid.canGoForward()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { grid.lastPage(); });
	paginator.append(link);
}; 


function showAddForm() {
  if ( $("#addform").is(':visible') ) 
      $("#addform").hide();
  else
      $("#addform").show();
}

        

   




  



