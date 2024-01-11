let feedStart = 10;

let lastRequest = '';

function onSortActorClick($element)
{
  // change sorting will start feed at 0
  feedStart = 0;

  // default new sort order is ascending
  let sortOrder = 'asc';

  // if the current element is the active sorting actor,
  // determine the new order by inspecting the elements classes
  if ($element.hasClass('cs-sort-active-asc')) {
    sortOrder = 'desc';
  }

  // remove any previously set active and hide sort icons
  $('.cs-sort-actor')
    .removeClass('cs-sort-active cs-sort-active-asc cs-sort-active-desc')
    .find('span[uk-icon]')
    .addClass('uk-hidden');

  // set new active classes
  $element
    .addClass('cs-sort-active')
    .addClass('cs-sort-active-' + sortOrder);

  // set new sort icon class
  $element
    .find('span[uk-icon]')
    .removeClass('uk-hidden')
    .attr('uk-icon', 'triangle-' + (sortOrder === 'asc' ? 'up' : 'down'));

  // empty current feed content
  let el = $('.feed-load-more');
  if(el.length < 1){
    el = $('.feed-load-more-grid');
  }

  let target = el.data('feed').target;
  $(target).empty();

  // re-enable spinner - otherwise feeds are reaching their end before changing sort order
  // and will not be able to load more entries
  el.css('display', 'block');

  // request for more
  loadMore(el);
}

function loadMore(spinner: JQuery<HTMLElement>)
{
  // determine current sortBy
  let sortBy = '';
  let $sortActive = $('.cs-sort-active');

  if ($sortActive.length) {
    if ($sortActive.hasClass('cs-sort-active-asc')) {
      sortBy = $sortActive.data('sort-order').asc;
    } else {
      sortBy = $sortActive.data('sort-order').desc;
    }
  } else {
    sortBy = 'date';
  }

  let path = window.location.origin + spinner.data('feed').url  + feedStart + '/' + sortBy;
  let spinnerUrl = new URL(path);
  let spinnerParams = new URLSearchParams(spinnerUrl.search);

  // adopt current query parameter
  let currentUrl = new URL(window.location.href);
  let currentParams = new URLSearchParams(currentUrl.search);
  currentParams.forEach((value, key) => {
    spinnerParams.set(key, value);
  });

  let spinnerTarget = spinner.data('feed').target;
  if (spinnerTarget) {
    let lastArticle = $(spinnerTarget).find('article:last-child');
    if (lastArticle) {
      let lastItemId = lastArticle.data('item-id');

      if (lastItemId) {
        spinnerParams.set('lastId', lastItemId);
      }
    }
  }

  if (spinner.data('feed').query) {
    // augment additional data
    for (const [key, value] of Object.entries(spinner.data('feed').query)) {
      // @ts-ignore
      spinnerParams.set(key, value);
    }
  }

  // build up the url
  const url = new URL(`${spinnerUrl.origin}${spinnerUrl.pathname}?${spinnerParams}`).toString();

  if (lastRequest == url) {
    return;
  }
  lastRequest = url;

  // send ajax request to get more items
  $.ajax({
    url: url
  })
    .done(function(result) {
      try {
        let foundArticles = false;
        if ($(result).filter('article').length) {
          foundArticles = true;
        } else if ($(result).find('article').length) {
          foundArticles = true
        }

        if (foundArticles) {
          // append the data
          let target = spinner.data('feed').target;
          $(target).append(result);

          let event = new CustomEvent(
            'feedDidLoad',
            {
              detail: {
                feedStart: feedStart,
              },
              bubbles: true,
              cancelable: true
            }
          );
          window.dispatchEvent(event);

          // increase for next run
          feedStart += 10;

          if (isElementInViewport(spinner)) {
            loadMore(spinner);
          }
        } else {
          $('.feed-load-more, .feed-load-more-grid').css('display', 'none');
        }
      } catch (error) {
        $('.feed-load-more, .feed-load-more-grid').css('display', 'none');
      }
    });
}

function isElementInViewport (el: JQuery<HTMLElement>) {
  const rect = el[0].getBoundingClientRect();

  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}

export default function () {
  const $spinners = $('.feed-load-more, .feed-load-more-grid');

  $spinners.on('inview', function() {
    loadMore($(this));
  });

  $('.cs-sort-actor').on('click', function(event) {
    onSortActorClick($(this));
  });
}
