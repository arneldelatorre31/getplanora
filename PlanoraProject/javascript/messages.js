(function () {
  var chatItems = Array.prototype.slice.call(document.querySelectorAll(".chat-item"));
  var searchInput = document.getElementById("conversationSearch");
  var chatMessages = document.getElementById("chatMessages");
  var messageInput = document.getElementById("messageInput");
  var sendBtn = document.getElementById("sendBtn");
  var attachBtn = document.getElementById("attachBtn");
  var attachmentInput = document.getElementById("attachmentInput");
  var attachmentPreview = document.getElementById("attachmentPreview");
  var emojiBtn = document.getElementById("emojiBtn");
  var emojiPanel = document.getElementById("emojiPanel");
  var viewBookingBtn = document.getElementById("viewBookingBtn");
  var sidebarUnreadBadge = document.getElementById("sidebarUnreadBadge");
  var activeReceiverName = document.getElementById("activeReceiverName");
  var activeReceiverBusiness = document.getElementById("activeReceiverBusiness");
  var activeReceiverAvatar = document.getElementById("activeReceiverAvatar");
  var activeReceiverId = chatItems.length ? chatItems[0].getAttribute("data-receiver-id") : "";
  var lastMessageId = 0;
  var pollTimer = null;
  var selectedFile = null;
  var activeConversationOpened = false;

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function scrollBottom() {
    if (chatMessages) {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  }

  function updateUnreadBadge() {
    if (!sidebarUnreadBadge) {
      return;
    }

    fetch("../php/vendorChat.php?action=unread_count", {
      credentials: "same-origin"
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        var count;

        if (!data.success) {
          return;
        }

        count = parseInt(data.unreadCount, 10) || 0;

        if (count > 0) {
          sidebarUnreadBadge.hidden = false;
          sidebarUnreadBadge.textContent = count > 99 ? "99+" : count;
        } else {
          sidebarUnreadBadge.hidden = true;
          sidebarUnreadBadge.textContent = "";
        }
      });
  }

  function renderMessage(message) {
    var bubble = document.createElement("div");
    var body = "";

    if (document.querySelector('[data-message-id="' + message.id + '"]')) {
      lastMessageId = Math.max(lastMessageId, parseInt(message.id, 10) || 0);
      return;
    }

    bubble.className = "message " + message.direction;
    bubble.setAttribute("data-message-id", message.id);

    if (message.message) {
      body += "<p>" + escapeHtml(message.message) + "</p>";
    }

    if (message.attachmentPath) {
      body += '<a class="message-attachment" href="' + escapeHtml(message.attachmentPath) + '" target="_blank" rel="noopener">' +
        '<i class="fa-solid fa-paperclip"></i>' +
        '<span>' + escapeHtml(message.attachmentName || "Attachment") + '</span>' +
      "</a>";
    }

    body += "<span>" + escapeHtml(message.time || "") + "</span>";
    bubble.innerHTML = body;

    chatMessages.appendChild(bubble);
    lastMessageId = Math.max(lastMessageId, parseInt(message.id, 10) || 0);
  }

  function updateActiveHeader(item) {
    if (!item) {
      return;
    }

    activeReceiverId = item.getAttribute("data-receiver-id") || "";

    if (activeReceiverName) {
      activeReceiverName.textContent = item.getAttribute("data-name") || "Vendor";
    }

    if (activeReceiverBusiness) {
      activeReceiverBusiness.textContent = item.getAttribute("data-business") || "Vendor Service";
    }

    if (activeReceiverAvatar) {
      activeReceiverAvatar.src = item.getAttribute("data-avatar") || "../image/planoraLogo.jpg";
    }
  }

  function setActiveContact(item) {
    chatItems.forEach(function (chatItem) {
      chatItem.classList.remove("active-chat");
    });

    item.classList.add("active-chat");
    updateActiveHeader(item);
    activeConversationOpened = true;
    lastMessageId = 0;
    chatMessages.innerHTML = '<div class="empty-chat">Loading conversation...</div>';
    loadMessages(true, true);
  }

  function loadMessages(reset, markRead) {
    if (!activeReceiverId || !chatMessages) {
      return;
    }

    var afterId = reset ? 0 : lastMessageId;
    var shouldMarkRead = markRead ? 1 : 0;

    fetch("../php/vendorChat.php?action=list&receiver_id=" + encodeURIComponent(activeReceiverId) + "&after_id=" + encodeURIComponent(afterId) + "&mark_read=" + shouldMarkRead, {
      credentials: "same-origin"
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          return;
        }

        if (reset) {
          chatMessages.innerHTML = "";
        }

        data.messages.forEach(renderMessage);

        if (chatMessages.children.length === 0) {
          chatMessages.innerHTML = '<div class="empty-chat">No messages yet. Start the conversation.</div>';
        }

        updateUnreadBadge();
        scrollBottom();
      });
  }

  function updatePreview(text) {
    var activeItem = document.querySelector(".chat-item.active-chat");
    var preview = activeItem ? activeItem.querySelector("[data-preview]") : null;
    var time = activeItem ? activeItem.querySelector("[data-last-time]") : null;

    if (preview) {
      preview.textContent = text || "Attachment sent.";
    }

    if (time) {
      time.textContent = "Now";
    }
  }

  function clearAttachment() {
    selectedFile = null;

    if (attachmentInput) {
      attachmentInput.value = "";
    }

    if (attachmentPreview) {
      attachmentPreview.hidden = true;
      attachmentPreview.innerHTML = "";
    }
  }

  function sendMessage() {
    var text = messageInput ? messageInput.value.trim() : "";
    var formData;

    if (!activeReceiverId || (text === "" && !selectedFile)) {
      return;
    }

    formData = new FormData();
    formData.append("action", "send");
    formData.append("receiver_id", activeReceiverId);
    formData.append("message", text);

    if (selectedFile) {
      formData.append("attachment", selectedFile);
    }

    sendBtn.disabled = true;

    fetch("../php/vendorChat.php", {
      method: "POST",
      body: formData,
      credentials: "same-origin"
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          alert(data.message || "Unable to send message.");
          return;
        }

        if (chatMessages.querySelector(".empty-chat")) {
          chatMessages.innerHTML = "";
        }

        renderMessage(data.message);
        updatePreview(text || data.message.attachmentName);
        messageInput.value = "";
        clearAttachment();
        scrollBottom();
      })
      .finally(function () {
        sendBtn.disabled = false;
        messageInput.focus();
      });
  }

  chatItems.forEach(function (item) {
    item.addEventListener("click", function () {
      setActiveContact(item);
    });
  });

  if (searchInput) {
    searchInput.addEventListener("input", function () {
      var query = searchInput.value.trim().toLowerCase();

      chatItems.forEach(function (item) {
        var search = item.getAttribute("data-search") || "";
        item.style.display = !query || search.indexOf(query) !== -1 ? "" : "none";
      });
    });
  }

  if (sendBtn) {
    sendBtn.addEventListener("click", sendMessage);
  }

  if (messageInput) {
    messageInput.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        sendMessage();
      }
    });
  }

  if (attachBtn && attachmentInput) {
    attachBtn.addEventListener("click", function () {
      attachmentInput.click();
    });

    attachmentInput.addEventListener("change", function () {
      selectedFile = attachmentInput.files[0] || null;

      if (selectedFile && attachmentPreview) {
        attachmentPreview.hidden = false;
        attachmentPreview.innerHTML =
          '<i class="fa-solid fa-paperclip"></i>' +
          '<span>' + escapeHtml(selectedFile.name) + '</span>' +
          '<button type="button" id="clearAttachment">Remove</button>';

        document.getElementById("clearAttachment").addEventListener("click", clearAttachment);
      }
    });
  }

  if (emojiBtn && emojiPanel) {
    emojiBtn.addEventListener("click", function () {
      emojiPanel.hidden = !emojiPanel.hidden;
    });

    emojiPanel.querySelectorAll("button").forEach(function (button) {
      button.addEventListener("click", function () {
        messageInput.value += button.textContent;
        emojiPanel.hidden = true;
        messageInput.focus();
      });
    });

    document.addEventListener("click", function (event) {
      if (!event.target.closest("#emojiBtn") && !event.target.closest("#emojiPanel")) {
        emojiPanel.hidden = true;
      }
    });
  }

  if (viewBookingBtn) {
    viewBookingBtn.addEventListener("click", function () {
      var name = activeReceiverName ? activeReceiverName.textContent.trim() : "";
      window.location.href = "bookings.php?search=" + encodeURIComponent(name);
    });
  }

  if (chatItems.length) {
    updateActiveHeader(chatItems[0]);
    activeConversationOpened = true;
    loadMessages(true, true);
    updateUnreadBadge();
    pollTimer = setInterval(function () {
      loadMessages(false, activeConversationOpened);
      updateUnreadBadge();
    }, 3000);
  }

  window.addEventListener("beforeunload", function () {
    if (pollTimer) {
      clearInterval(pollTimer);
    }
  });
})();
