function addRow(tableID,Ldelete,Litem,Ltype1,Ltype2,Ltype3,Ltype4,Ltype5,Ltype6)
{
	var table = document.getElementById(tableID);
	var rowCount = table.rows.length;
	var row = table.insertRow(rowCount); //vlozi prazdny riadok do tabulky + vrati referenciu
	var colCount = table.rows[1].cells.length; // zrata pocet buniek (v 2.riadku)
	for(var i=0; i<colCount; i++)
	{
		var newcell = row.insertCell(i); // vlozi do noveho riadka bunku + vrati na nu odkaz
		var newmax = parseInt(table.rows[rowCount-1].cells[1].innerHTML) + 1; //+1 vyssie id.
		//alert(i);
		switch (i)
		{
			case 0: newcell.innerHTML = '<input type="button" value="'+Ldelete+'" onclick="deleteRow(this)" />';
				break;
			case 1: newcell.innerHTML = newmax;
				break;
			case 2: newcell.innerHTML = '<input name="label'+newmax+'" type="text" value="'+Litem+' '+newmax+'" style="width:120px;" />';
				break;
			case 3: var element = document.createElement('select');
				element.options[0] = new Option(Ltype1, 'input');
				element.options[1] = new Option(Ltype2, 'checkbox');
				element.options[2] = new Option(Ltype3, 'radio');
				element.options[3] = new Option(Ltype4, 'select');
				element.options[4] = new Option(Ltype5, 'textarea');
				element.options[5] = new Option(Ltype6, 'textarea');
				element.name = 'type'+newmax;
				element.selectedIndex = 0;
				newcell.innerHTML = ' '; newcell.appendChild(element);
				//newcell.onchange = changeEvent;
				if (element.addEventListener) {
					element.addEventListener('change',changeEvent,false);
					//element.addEventListener('change',function () { checkSelectedType(this); },false);
				}
				else if (element.attachEvent) {
					element.attachEvent('onchange',changeEvent);
				}
				break;
			case 4: newcell.innerHTML = '<input name="valid'+newmax+'" type="text" />';
				break;
			case 5: var element = document.createElement('input');
				element.setAttribute('type', 'text');
				element.setAttribute('value', '');
				element.setAttribute('name', 'multi_values'+newmax);
				element.style.display = 'none';
				newcell.appendChild(element);
				//newcell.innerHTML = '<input name="multi_values'+newmax+'" type="text" />';
				break;
		}
		document.getElementById('maxval').value = newmax+'';
		//alert(document.getElementById('maxval').value);
		
		$(table).tableDnD({
			//onDragClass: "myDragClass",
			onDrop: function(table, row) {
				reindexRows();
			}
		});
		
	}
}

function changeEvent(e)
{
	target = (e.target) ? e.target : e.srcElement;
	//alert(target);
	checkSelectedType(target); //object HTMLSelectElement
}

function deleteRow(obj)//obj==select
{
	var delRow = obj.parentNode.parentNode;
	var table = delRow.parentNode.parentNode;
	if (table.rows.length<=2)
		return;
	table.deleteRow(delRow.sectionRowIndex+1);
	reindexRows();
}

function checkSelectedType(obj)
{
	var objRow = obj.parentNode.parentNode;
	//whether display or not the text field for multiple values
	if (objRow.cells[5].childNodes[0] && objRow.cells[5].childNodes[0].style)
		objRow.cells[5].childNodes[0].style.display = (obj.value=='radio' || obj.value=='select') ? 'block' : 'none';
	if (objRow.cells[5].childNodes[1] && objRow.cells[5].childNodes[1].style)
		objRow.cells[5].childNodes[1].style.display = (obj.value=='radio' || obj.value=='select') ? 'block' : 'none';
}

function reindexRows() //reindexes (=renames) form elements
{
	$('#dataTable tbody').find('tr').each(function(i){
		var j=i+1;
		this.id = 'row'+j;
		var cells=this.getElementsByTagName('td');
		//alert(cells.length);
		var t;
		cells[1].innerHTML=j;
		t=cells[2].getElementsByTagName('input');
		t[0].name='label'+j;
		t=cells[3].getElementsByTagName('select');
		t[0].name='type'+j;
		t=cells[4].getElementsByTagName('input');
		t[0].name='valid'+j;
		t=cells[5].getElementsByTagName('input');
		t[0].name='multi_values'+j;
	});
}

$(document).ready(function(){
	$("#dataTable tbody").tableDnD({
		//onDragClass: "myDragClass",
		onDrop: function(table, row) {
			reindexRows();
			/*var rows = table.tBodies[0].rows;
			var debugStr = "Row dropped was "+row.id+". New order: ";
			for (var i=0; i<rows.length; i++) {
				debugStr += rows[i].id+" ";
			}
			$("#debugArea").html(debugStr);*/
		},
		/*onDragStart: function(table, row) {
			var cell=$(row).find('td')[1];
			cell.innerHTML='<b>'+cell.innerHTML+'</b>';
			$("#debugArea").html("Started dragging row "+row.id);
		}*/
	});
});



function switch_language(elem)
{
	var loc=window.location.href;
	var pos=loc.indexOf('?');
	if (pos>-1)
		loc = window.location.href.substr(0,pos);
	location.href = loc + "?iLanguage=" + elem.value;
}


