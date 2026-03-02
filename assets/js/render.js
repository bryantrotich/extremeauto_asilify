/*!
 * Signer
 * Version 1.0 - built Sat, Oct 6th 2018, 01:12 pm
 * https://simcycreative.com
 * Simcy Creative - <hello@simcycreative.com>
 * Private License
 */

var pdfDoc = null,
    pageNum = 1,
    pageRendering = false,
    pageNumPending = null,
    password = null;
    canvas = document.getElementById('document-viewer'),
    ctx = canvas.getContext('2d');

if ($(window).width() > 414) {
    var scale = 1.1;
}else{
    var scale = 0.6;
}

/**
 * Get page info from document, resize canvas accordingly, and render page.
 * @param num Page number.
 */
function renderPage(num) {
    $(".document-load").show();
    $(".signer-element").hide();
    pageRendering = true;
    // Using promise to fetch the page
    pdfDoc.getPage(num).then(function(page) {
        var viewport = page.getViewport($(".document-map").width() / page.getViewport(scale).width);
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        var renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        var renderTask = page.render(renderContext);

        // Wait for rendering to finish
        renderTask.promise.then(function() {
            $(".document-load").hide();
            $("[page="+pageNum+"]").show();

            if (pageNum == pdfDoc.numPages) {
                $("#next").addClass("disabled");
            } else {
                $("#next").removeClass("disabled");
            }

            if (pageNum == 1) {
                $("#prev").addClass("disabled");
            } else {
                $("#prev").removeClass("disabled");
            }

            pageRendering = false;
            if (pageNumPending !== null) {
                // New page rendering is pending
                renderPage(pageNumPending);
                pageNumPending = null;
            }
        });
    });

    // Update page counters
    $("#page_num").text(num);
    pageNum = num;

}



/**
 * If another page rendering in progress, waits until the rendering is
 * finised. Otherwise, executes rendering immediately.
 */
function queueRenderPage(num) {
  if (pageRendering) {
    pageNumPending = num;
  } else {
    renderPage(num);
  }
}

/**
 * Displays previous page.
 */
function onPrevPage() {
  if (pageNum <= 1) {
    return;
  }
  pageNum--;
  queueRenderPage(pageNum);
}
$("#prev").click(function(event){
    event.preventDefault();
    onPrevPage();
});
// document.getElementById('prev').addEventListener('click', onPrevPage);

/**
 * Displays next page.
 */
function onNextPage() {
  if (pageNum >= pdfDoc.numPages) {
    return;
  }
  pageNum++;
  queueRenderPage(pageNum);
}
$("#next").click(function(event){
    event.preventDefault();
    onNextPage();
});
// document.getElementById('next').addEventListener('click', );


/**
 * Asynchronously downloads PDF.
 */
openDocument(pdfDocument);

function openDocument(url) {

    PDFJS.getDocument({
        url: url
    }).then(function(pdfDoc_) {
        pdfDoc = pdfDoc_;
        document.getElementById('page_count').textContent = pdfDoc.numPages;

        // Initial/first page rendering
        renderPage(pageNum);

        if (pdfDoc.numPages == 1) {
            $("#next, #prev").addClass("disabled");
        }
    }).catch(function(error) {
        $(".document-error").find(".error-message").text(error.message);
        $(".document-load").hide();
        $(".document-error").show();
    });

}

/*
 * Zoom in and Zoom Out
 */
$(".btn-zoom").click(function(){
    if($(this).attr("zoom") === "plus"){
        scale = scale - 0.1;
    }else{
        if (scale > 0) {
            scale = scale + 0.1;
        }
    }

    if (scale == 1) {
        $("#document-viewer").css("max-width", "100%");
    }else{
        $("#document-viewer").width("auto");
    }

    renderPage(pageNum);
});

