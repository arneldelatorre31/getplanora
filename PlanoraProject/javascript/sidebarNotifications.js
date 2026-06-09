(function () {
  var messagesLink = document.querySelector('.sidebar-menu a[href="messages.php"]');
  var badge = document.getElementById("sidebarUnreadBadge");
  var pollTimer = null;

  if (!messagesLink) {
    return;
  }

  function ensureStyles() {
    if (document.getElementById("sidebarNotificationStyles")) {
      return;
    }

    var style = document.createElement("style");
    style.id = "sidebarNotificationStyles";
    style.textContent =
      ".sidebar-unread-badge{" +
      "margin-left:auto;min-width:24px;height:24px;padding:0 7px;border-radius:999px;" +
      "background:#d4a84f;color:#000;display:inline-flex;align-items:center;justify-content:center;" +
      "font-size:12px;font-weight:800;line-height:1;box-shadow:0 0 16px rgba(212,168,79,.35);" +
      "border:1px solid rgba(255,255,255,.16);" +
      "}" +
      ".sidebar-unread-badge[hidden]{display:none!important;}";
    document.head.appendChild(style);
  }

  function ensureBadge() {
    if (!badge) {
      badge = document.createElement("span");
      badge.id = "sidebarUnreadBadge";
      badge.hidden = true;
      messagesLink.appendChild(badge);
    }

    badge.classList.add("sidebar-unread-badge");
  }

  function setBadge(count) {
    if (count > 0) {
      badge.hidden = false;
      badge.textContent = count > 99 ? "99+" : String(count);
      messagesLink.setAttribute("aria-label", "Messages, " + count + " unread");
    } else {
      badge.hidden = true;
      badge.textContent = "";
      messagesLink.setAttribute("aria-label", "Messages");
    }
  }

  function refreshUnreadCount() {
    fetch("../php/vendorChat.php?action=unread_count", {
      credentials: "same-origin"
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          return;
        }

        setBadge(parseInt(data.unreadCount, 10) || 0);
      })
      .catch(function () {
        // Keep the current badge state if the count cannot be fetched.
      });
  }

  ensureStyles();
  ensureBadge();
  refreshUnreadCount();
  pollTimer = setInterval(refreshUnreadCount, 8000);

  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) {
      refreshUnreadCount();
    }
  });

  window.addEventListener("beforeunload", function () {
    if (pollTimer) {
      clearInterval(pollTimer);
    }
  });
})();
