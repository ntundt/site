function CreateRequest() {
	var Request = false;
	if (window.XMLHttpRequest) {
		Request = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		try {
			 Request = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (CatchException) {
			 Request = new ActiveXObject("Msxml2.XMLHTTP");
		}
	}
	if (!Request) {
		alert("Невозможно создать XMLHttpRequest");
	}
	return Request;
}

function SendRequest(r_method, r_path, r_args, r_handler) {
    var Request = CreateRequest();
    if (!Request) {
    	return;
    }
    Request.onreadystatechange = function() {
    	if (Request.readyState == 4) {
    		r_handler(Request);
    	}
    }
    if (r_method.toLowerCase() == "get" && r_args.length > 0)
    r_path += "?" + r_args;
    Request.open(r_method, r_path, true);
    if (r_method.toLowerCase() == "post") {
    	Request.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=utf-8");
    	Request.send(r_args);
    } else {
    	Request.send(null);
    }
} 

function SendLongPollRequest(r_method, r_path, r_args, r_handler) {
	if(chatIn) {
    	var Request = CreateRequest();
    	if (!Request) {
    		return;
    	}
    	Request.onreadystatechange = function() {
    		if (Request.readyState == 4) {
    			r_handler(Request);
    			SendLongPollRequest(r_method, r_path, r_args, r_handler);
    		}
    	}
    	if (r_method.toLowerCase() == "get" && r_args.length > 0)
    	r_path += "?" + r_args;
    	Request.open(r_method, r_path, true);
    	if (r_method.toLowerCase() == "post") {
    		Request.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=utf-8");
    		Request.send(r_args);
    	} else {
    		Request.send(null);
    	}
	}
} 