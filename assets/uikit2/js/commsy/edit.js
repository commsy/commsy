;(function(UI) {

  "use strict";

  let promises = [];
  let draftMode = false;

  UI.component('edit', {

    defaults: {
      editUrl: ''
    },

    boot: function() {
      // init code
      UI.ready(function(context) {
        UI.$("[data-cs-edit]", context).each(function() {
          let element = UI.$(this);

          if (!element.data("edit")) {
            UI.edit(element, UI.Utils.options(element.attr("data-cs-edit")));
          }
        });
      });
    },

    init: function() {
      let $this = this;
      let element = $this.element[0];

      // Look for div.cs-delete and show on mouseover. This is to show the delete icon on bigger screens
      // only if you hover the whole area.
      $(element)
        .mouseover(function() {
          $(this).find('div[data-delete]').toggleClass('uk-invisible', false);
        })
        .mouseout(function() {
          $(this).find('div[data-delete]').toggleClass('uk-invisible', true);
        });

      $this.registerArticleEvents(element);
    },

    registerArticleEvents: function(element) {
      let $this = this;
      let $element = $(element);

      // This allows for specifying the toggle element by options and will fall back to the default cs-edit selector
      let editToggle = $this.options.toggleSelect ?
        $($this.options.toggleSelect) :
        $(element).find('div.cs-edit');

      editToggle.find('a').attr('data-uk-tooltip', '');
      editToggle.find('a').attr('title', editToggle.data('edit-title'));

      // show articles as selected, when mouseover the edit icon
      editToggle
        .mouseenter(function() {
          if (!$(this).closest('article').find('.cs-readmoreless:first').parent("a").hasClass('uk-invisible')) {
            $(this).parents(".cs-edit-section").find(".fade-preview").toggleClass("uk-hidden", true);
          }
          $(this).parents(".cs-edit-section").toggleClass('cs-selected', true);
        })
        .mouseleave(function() {
          if (!$(this).closest('article').find('.cs-readmoreless:first').parent("a").hasClass('uk-invisible') &&
            !$(this).closest('article').find('.cs-toggle-preview-small').hasClass('cs-toggle-full')) {
            $(this).parents(".cs-edit-section").find(".fade-preview").toggleClass("uk-hidden", false);
          }
          $(this).parents(".cs-edit-section").toggleClass('cs-selected', false);
        });

      // send ajax requests on click to load the form
      editToggle.click(function(event) {
        event.preventDefault ? event.preventDefault() : (event.returnValue = false);

        // reset article selection class and remove event handling
        $(this).parents('.cs-edit-section').toggleClass('cs-selected', false);
        $(this).off();

        $this.onClickEdit($element);
      });

      // If the entry is a draft fake the edit click in order to bring the UI in edit mode.
      const draftElement = $element.find('div.cs-edit-draft');
      if (draftElement.length > 0) {
        $this.onClickEdit($element);
      }
    },

    onClickEdit: function($section) {
      let $this = this;

      // show the loading spinner
      $section.find('.cs-edit-spinner').toggleClass('uk-hidden', false);

      let editButtons = $('.cs-edit');
      editButtons.removeClass('cs-edit');
      editButtons.each(function() {
        $(this).find('a').addClass('uk-hidden');
      });

      $(".cs-additional-actions")
        .addClass('uk-hidden')
        .parent().find("button.uk-button").addClass("uk-text-muted");

      // send ajax request to get edit html
      $.ajax({
        url: this.options.editUrl,
      })
        .done(function(result, textStatus, jqXHR) {
          // replace article html
          $section.html($(result));
          registerDraftFormButtonEvents();

          $this.handleFormSubmit($section);

          // Trigger a resize event. This is a workaround for the data-uk-grid component for example used
          // by hashtags. There is some odd behaviour after replacing the content with ajax. Sometimes labels
          // which are too long become truncated. However, data-uk-grid-match will now adjust the height of all
          // columns in a row.
          UI.trigger('resize');
        })
        .fail(function() {
          // We might get a 403 response if there is an active lock or an error response in general.
          window.location.reload();
        });
    },

    handleFormSubmit: function(article) {
      let $this = this;

      // override form submit behaviour
      article.find('button').click(function (event) {
        let $button = $(this);
        let buttonNameAttr = $button.attr('name');

        event.preventDefault();

        // If the cancel button is clicked, remote the edit lock and request the correct redirect url
        if (buttonNameAttr.indexOf('cancel') > -1) {
          $.ajax({
            url: $this.options.cancelEditUrl,
            type: "POST",
            data: null
          })
            .done(function (result) {
              window.location.replace(result.redirectUrl);
            });

          return;
        }

        // Skip, if the button adds a new hashtag
        if (buttonNameAttr.indexOf('newHashtagAdd') > -1 || buttonNameAttr.indexOf('itemLinks[newHashtagAdd]') > -1) {
          return;
        }

        const form = $(this).closest('form');
        $this.fixDateInput($('#date_start_date'));
        $this.fixDateInput($('#date_end_date'));

        let formData = form.serializeArray();
        formData.push({name: this.name, value: this.value});

        const promise = $.ajax({
          url: $this.options.editUrl,
          type: "POST",
          data: formData
        }).promise()
          .then((res) => {
              let $result = $(res);

              // Replace the dom content with the response from the request
              article.html($result);

              // If the response contains any form errors, prepare re-handling
              if ($result.find('ul.form-errors').length) {
                registerDraftFormButtonEvents();
                $this.handleFormSubmit(article);

                throw new Error("Form invalid");
              }
            }
          );

        promises.push(promise);
      });
    },

    // TODO: Is this really needed anymore???
    fixDateInput: function($element) {
      if ($element.length) {
        // this will convert dd.mm.YY => dd.mm.20YY
        const parts = $element.val().split('.');
        if (parts.length === 3 && parts[2].length === 2) {
          $element.val(`${a[0]}.${a[1]}.20${a[2]}`);
        }

        // Make sure the input is enabled
        $element.prop('disabled', false);
      }
    }
  });

  $('div[data-delete]').on('click', function(e) {
    e.preventDefault();

    let $delete = $(this);

    UIkit.modal.confirm($delete.data('delete-confirm'), function() {
      $.ajax({
        url: $delete.data('delete-url'),
      }).done(function(data, textStatus, jqXHR) {
        if (data.deleted) {
          window.location.reload(true);
        }
      }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('fail')
      });
    }, function () {
    }, {
      labels: {
        Cancel: $delete.data('confirm-delete-cancel'),
        Ok: $delete.data('confirm-delete-confirm')
      }
    });
  });

  function sendForms(undraftUrl = '') {
    Promise.all(promises)
      .then(() => new Promise((resolve) => {
        // All form sections and have been saved
        // If this is a draft we have to undraft the item
        const undraftPromise = $.ajax({
          url: undraftUrl,
          type: "POST",
          data: null
        }).promise();

        resolve(draftMode ? undraftPromise : true);
      }))
      .then(() => {
        // Do final reload to refresh the page
        window.location.reload();
      })
      .catch((error) => {
        console.error(error.message);
      });
  }

  let registerDraftFormButtonEvents = function() {
    const $draftSave = $('[data-draft-save]');
    const $draftCancel = $('[data-draft-cancel]');

    /**
     * This should not be mandatory in order to ensure the event listener is only fired once
     * due to the .one() call. However, it fixes the problem where the handler is called multiple
     * times, resulting in a lot of unwanted ajax requests when saving.
     */
    $draftSave.off('click');

    /**
     * Use of .on() (instead of .one()) is needed to also report invalid form states
     * if the user submits the (combined) form for a second time or more often.
     */
    $draftSave.on('click', function (event) {
      event.preventDefault();

      draftMode = true;
      promises = [];

      const itemType = $(this).parents('#draft-buttons-wrapper').data("item-type");
      const formElements = $(this).parents('article').find('form');

      // Check for any invalid forms
      // reportValidity() will also display the invalidity to the user
      const invalidForms = formElements.filter(function() {
        return this.reportValidity() === false;
      });

      if (invalidForms.length === 0) {
        // Simulate a click on each individual form submit button
        $(formElements).find('.uk-button-primary').click();

        // Discussion articles will not use ajax at all to create a new answer???
        if (itemType === "article") {
          return;
        }

        // Resolve all collected promises
        const undraftUrl = $(this).data('draft-save').undraftUrl;
        sendForms(undraftUrl);
      }
    });

    $draftCancel.one('click', function (event) {
      event.preventDefault();

      let $itemType = $(this).parents('#draft-buttons-wrapper').data("item-type");
      if ($itemType === "section" || $itemType === "step" || $itemType === "article") {
        // return to detail view of the entry
        window.location.reload(true);
      } else {
        // return to list view
        let pathParts = window.location.pathname.split("/");
        pathParts.pop();
        window.location.href = pathParts.join("/");
      }
    });
  }

  registerDraftFormButtonEvents();

})(UIkit);
