(function ($) {
  $(document).ready(function () {
    const btnKeywordSubmit = $("#js-ksr-submit-keyword");
    const keywordInput = $("#js-ksr-keyword");

    const ksrGetPosts = (e) => {
      if (!keywordInput.val()) {
        alert("Please enter a keyword!");
        keywordInput.focus();
        return;
      }

      var data = {
        val: keywordInput.val(),
        action: "ajax_get_posts",
      };

      $.post({
        url: ksr_ajax_object.ajaxurl,
        data: data,
        success: function (data) {
          $("#js-ksr-posts").empty().append(data);
        },
      });
    };

    const ksrUpdatePosts = (e) => {
      const newKeywordInput = $(e.target).parent().find("input");
      const wrapper = $(e.target).parents('.ksr-posts__wrapper');
      const type = $(e.target).data("type");

      if (!newKeywordInput.val()) {
        alert("Please enter a keyword!");
        newKeywordInput.focus();
        return;
      }

      if (!keywordInput.val()) {
        alert("Please enter a keyword!");
        keywordInput.focus();
        return;
      }

      var data = {
        type,
        oldKeyword: keywordInput.val(),
        newKeyword: newKeywordInput.val(),
        action: "ajax_update_posts",
      };

      $.post({
        url: ksr_ajax_object.ajaxurl,
        data: data,
        success: function (data) {
            wrapper.find('.ksr-posts__form').remove();
            wrapper.find('.ksr-posts__list').remove();
            wrapper.append('<p>All posts updated</p>');
        },
      });
    };

    $(document).on("click", "#js-keyword-btn", ksrUpdatePosts);
    btnKeywordSubmit.on("click", ksrGetPosts);
    keywordInput.keyup(function(event) {
        if (event.keyCode === 13) {
            ksrGetPosts();
        }
    });
  });
})(jQuery);
