/*--------------------------------------------------|
| dTree 2.05 | www.destroydrop.com/javascript/tree/ |
|---------------------------------------------------|
| Copyright (c) 2002-2003 Geir Landr�               |
|                                                   |
| This script can be used freely as long as all     |
| copyright messages are intact.                    |
|                                                   |
| Updated: 17.04.2003                               |
|--------------------------------------------------*/
// Node object
function Node(id, pid, name, url, title, target, icon, iconOpen, open) 
// Tree object
function dTree(objName, icons) 
// Adds a new node to the node array
// Open/close all nodes
	this.oAll(true);
};
dTree.prototype.closeAll = function() 
	this.oAll(false);
};
// Outputs the tree to the page
dTree.prototype.toString = function() 
	var str = '<div class="dtree">\n';
	if (document.getElementById) {
		if (this.config.useCookies) this.selectedNode = this.getSelected();
	this.completed = true;
	return str;
};
// Creates the tree structure
// Creates the node icon, url and text

// Adds the empty and line icons
// Checks if a node has any children and if it is the last sibling
// Returns the selected node
// Highlights the selected node
// Toggle Open or close

// Open or close all nodes
// [Cookie] Returns ids of open nodes as a string