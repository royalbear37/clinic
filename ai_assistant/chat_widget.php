<!-- ai_assistant/chat_widget.php -->
<style>
    .chat-entry {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 12px;
    }

    .chat-entry img {
        width: 32px;
        height: 32px;
        border-radius: 9999px;
    }

    .chat-content {
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-width: 260px;
    }

    .chat-header {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #555;
    }

    .chat-bubble {
        padding: 8px 12px;
        background-color: #f1f1f1;
        border-radius: 15px;
        border-top-left-radius: 0;
        font-size: 14px;
    }

    .chat-user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .chat-user .chat-bubble {
        background-color: #007bff;
        color: white;
        border-top-right-radius: 0;
        border-top-left-radius: 15px;
    }

    #chat-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #007bff;
        color: white;
        padding: 10px 16px;
        font-size: 15px;
        border-radius: 999px;
        cursor: pointer;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    #chat-box {
        display: none;
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 320px;
        height: 450px;
        background: white;
        border: 1px solid #ccc;
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        z-index: 9998;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #chat-box .header {
        background: #007bff;
        color: white;
        padding: 10px;
        text-align: center;
        font-weight: bold;
    }

    #chat-log {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        background-color: #f9f9f9;
    }

    .input-row {
        display: flex;
        padding: 10px;
        border-top: 1px solid #ccc;
        gap: 6px;
    }

    #chat-box input[type="text"] {
        flex: 1;
        border: 1px solid #ccc;
        padding: 6px;
        border-radius: 6px;
        font-size: 14px;
    }

    #chat-box button {
        background: #007bff;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
    }

    #chat-box button:hover {
        background: #0056b3;
    }
</style>

<div id="chat-btn">💬 聊天</div>
<div id="chat-box">
    <div class="header">AI 小幫手</div>
    <div id="chat-log"></div>
    <div class="input-row">
        <input type="text" id="user-input" placeholder="輸入您的問題">


        <button onclick="sendMessage()">送出</button>
    </div>
</div>

<script>
    document.getElementById("chat-btn").onclick = function() {
        const box = document.getElementById("chat-box");
        box.style.display = box.style.display === "none" ? "flex" : "none";
    };

    function appendMessage(content, name, avatar, time, isUser = false) {
        const container = document.createElement("div");
        container.className = "chat-entry" + (isUser ? " chat-user" : "");

        const img = document.createElement("img");
        img.src = avatar;
        img.alt = name;

        const contentBox = document.createElement("div");
        contentBox.className = "chat-content";

        const header = document.createElement("div");
        header.className = "chat-header";
        header.innerHTML = `<strong>${name}</strong><span>${time}</span>`;

        const bubble = document.createElement("div");
        bubble.className = "chat-bubble";
        bubble.innerHTML = content;

        contentBox.appendChild(header);
        contentBox.appendChild(bubble);

        container.appendChild(img);
        container.appendChild(contentBox);

        document.getElementById("chat-log").appendChild(container);
        document.getElementById("chat-log").scrollTop = document.getElementById("chat-log").scrollHeight;
    }

    function sendMessage() {
        const input = document.getElementById("user-input");
        const msg = input.value.trim();
        if (!msg) return;

        const now = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
        appendMessage(msg, "你", "/clinic/ai_assistant/assets/user.png", now, true);

        fetch("/clinic/ai_assistant/chat_api.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    message: msg
                })
            })
            .then(res => res.json())
            .then(data => {
                const replyText = data.reply || "(⚠️ 無回應)";
                appendMessage(replyText.replace(/\n/g, '<br>'), "AI 小幫手", "/clinic/ai_assistant/assets/ai.png", now, false);
            })
            .catch(err => {
                console.error("❌ 錯誤：", err);
                appendMessage("無法取得回應，請稍後再試。", "AI 小幫手", "/clinic/ai_assistant/assets/ai.png", now, false);
            });

        input.value = "";
    }
</script>