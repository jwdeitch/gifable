angular.module("gifable.index.templates").run(["$templateCache", function($templateCache) {$templateCache.put("components/index/index.html","<div class=\"table\">\r\n    <div class=\"table-cell\">\r\n        <div class=\"logo\">\r\n            <img src=\"/img/index-logo.png\">\r\n            <p>Convert GIFs to HTML5 videos.</p>\r\n        </div>\r\n        <div class=\"uploader\">\r\n            <div ng-show=\"fileUploader.isHTML5\">\r\n                <div class=\"file-drop-zone\" nv-file-drop uploader=\"fileUploader\"><img src=\"/img/index-upload.png\"></div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n</div>\r\n");}]);