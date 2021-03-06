if (location.protocol !== 'https:') {
  location.replace(`https:${location.href.substring(location.protocol.length)}`);
}
Dropzone.options.upload = {
    paramName: "file", // The name that will be used to transfer the file
    maxFilesize: 75161927680, // MB
    timeout: 0,
    createImageThumbnails: false,
    sending: function(file, xhr) {
      console.log(file)
        xhr.setRequestHeader('upload-filename', file.name);
        if(file.type != ""){
          xhr.setRequestHeader('Content-Type', file.type);
        } else {
          xhr.setRequestHeader('Content-Type', "application/octet-stream");
        }
        var _send = xhr.send;
        xhr.send = function() {
          _send.call(xhr, file);
        }
    },
    success: function(file, server_resp) {
      if(server_resp != 0){
        try{
          var id           = '';
          var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
          var charactersLength = characters.length;
          for ( var i = 0; i < 8; i++ ) {
              id += characters.charAt(Math.floor(Math.random() * charactersLength));
          }
          sfile = {};
          sfile.size = file.size;
          sfile.name = file.name;
          localStorage.setItem(id,JSON.stringify({server_resp:server_resp,file:sfile}));
          window.mkfilepreview(file,this,id)
        } catch (_fs){
          console.log(_fs)
          alert("upload failed")
        }
      }
    },
    init: function(){
      for(lsz in localStorage){
        try{
          file_ = JSON.parse(localStorage.getItem(lsz))
          if(!file_.server_resp) file_ = null
        } catch (sjs){
          file_ = null;
        }
        if(!file_) continue;
        file_["file"].status = Dropzone.SUCCESS;
        file_["file"].accepted = true;
        this.emit("addedfile", file_["file"]);
        this.emit("complete", file_["file"]);
        console.log("b",this.files.push(file_["file"]));
        window.mkfilepreview(file_["file"], this, lsz)
      }
    }
};
window.mkfilepreview = function(file, _this, id){
let server_resp = localStorage.getItem(id);
server_resp = JSON.parse(server_resp);
server_resp = server_resp["server_resp"]
let dlurl = "https://"+window.location.hostname+"/f/"+server_resp.fLink;
let anchorEl = document.createElement('a');
anchorEl.setAttribute('href',dlurl);
anchorEl.setAttribute('target','_blank');
anchorEl.innerHTML = "<br>Download link";
file.previewTemplate.style.textAlign = "center";
file.previewTemplate.appendChild(anchorEl);
let rmurl = "https://"+window.location.hostname+"/delete/"+server_resp.hash;
anchorEl = document.createElement('a');
console.log(rmurl,id)
anchorEl.addEventListener("click", async function (e) {
  let server_resp = JSON.parse(localStorage.getItem(id));
  server_resp = server_resp["server_resp"]
  let rmurl = "https://"+window.location.hostname+"/delete/"+server_resp.hash;
  console.log(rmurl,id)
  if(confirm("Are you sure you want to delete this file?")){
    zre = await (await fetch(rmurl)).json();
    if(zre.ok){
      localStorage.removeItem(id);
      _this.removeFile(file);
    } else {
      alert("Error.");
    }
  }
})
anchorEl.setAttribute('href',"#");
anchorEl.innerHTML = "<br><br>Delete file";
file.previewTemplate.style.textAlign = "center";
file.previewTemplate.appendChild(anchorEl);
};
document.addEventListener('DOMContentLoaded', async function() {
let za_ = await (await fetch("/stats")).json();
document.getElementById("dsal").innerHTML = za_.bandwidth;
document.getElementById("dsala").innerHTML = za_.storage
}, false);