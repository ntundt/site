title = document.getElementById("titler");
lastprtext = document.getElementById("previewText").value;
setInterval(function() {
    if(lastprtext != document.getElementById("previewText").value) {
        lastprtext = document.getElementById("previewText").value;
        SendRequest("post", "https://hotstagemod.tk/wikify.php", "t="+document.getElementById("previewText").value, function(text) {
            document.getElementById("itemText").innerHTML = text.responseText;
        });
    }
    title.innerHTML = name;
    name = document.getElementById("name").value;
    document.getElementById("img").src = document.getElementById("imgpath").value;
}, 1000);