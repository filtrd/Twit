export function initFeed({ bindLoadedPostActions } = {}) {
    const feed = document.getElementById('feed');
    const feedSentinel = document.getElementById('feed-sentinel');
    const feedStatus = document.getElementById('feed-status');

    if (!feed || !feedSentinel || !('IntersectionObserver' in window)) return;

    const profileUsername = feed.dataset.profileUsername || '';
    const endpoint = profileUsername
        ? 'profile-feed.php?u=' + encodeURIComponent(profileUsername) + '&cursor='
        : 'feed.php?cursor=';

    let loadingFeed = false;
    let feedObserverArmed = false;

    const observer = new IntersectionObserver(entries => {
        const entry = entries[0];
        if (!entry) return;

        if (!entry.isIntersecting) {
            feedObserverArmed = false;
            return;
        }

        if (!feedObserverArmed || loadingFeed) return;
        feedObserverArmed = false;
        observer.disconnect();
        loadMorePosts();
    }, { rootMargin: '0px' });

    async function loadMorePosts() {
        if (loadingFeed || feed.dataset.hasMore !== '1') return;
        const cursor = feed.dataset.nextCursor;
        if (!cursor) return;

        loadingFeed = true;
        feedObserverArmed = false;
        observer.disconnect();
        if (feedStatus) {
            feedStatus.hidden = false;
            feedStatus.textContent = 'Loading more posts…';
        }

        try {
            // Temporary delay for testing infinite-scroll pagination visibility.
            await new Promise(resolve => setTimeout(resolve, 3000));
            const response = await fetch(endpoint + encodeURIComponent(cursor), {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Feed request failed');

            const data = await response.json();
            if (data.html) feed.insertAdjacentHTML('beforeend', data.html);
            feed.dataset.nextCursor = data.next_cursor || '';
            feed.dataset.hasMore = data.has_more ? '1' : '0';

            if (bindLoadedPostActions) bindLoadedPostActions();
            if (!data.has_more && feedStatus) feedStatus.hidden = true;
        } catch (error) {
            if (feedStatus) {
                feedStatus.hidden = false;
                feedStatus.textContent = 'Could not load more posts. Try again.';
            }
        } finally {
            loadingFeed = false;
            armFeedObserver();
        }
    }

    function armFeedObserver() {
        if (loadingFeed || feedObserverArmed || feed.dataset.hasMore !== '1') return;
        feedObserverArmed = true;
        observer.observe(feedSentinel);
    }

    armFeedObserver();

    window.addEventListener('scroll', () => {
        armFeedObserver();
    }, { passive: true });
}
