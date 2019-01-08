$(document).ready(function(){
  $(".dz-preview").each(function() {
    let preview = $(this);
    preview.find("img[data-dz-thumbnail]").each(function(e) {
      let imgThumbnail = $(this);
      let srcAttr = imgThumbnail.attr("src");
      if (srcAttr && srcAttr.startsWith("/http")) {
        imgThumbnail.attr("src", srcAttr.substring(1));
      }
    });
    preview.find("a[data-dz-view]").each(function(e) {
      let dzView = $(this);
      dzView.click(function() {
        let href = dzView.attr("href");
        if (href.indexOf("/http") >= 0) {
          var newHref = href.substring(href.indexOf("/http")+1);
          dzView.attr("href", newHref);
        }
      });
    });
    preview.find("a[data-dz-remove]").each(function(e) {
      $(this).remove();
    });
  });
});
