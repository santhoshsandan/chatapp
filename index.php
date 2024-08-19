<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <style>
    body { width: 600px; font-family: calibri; margin: 0; padding: 0; }
    .error { color: #FF0000; }
    .chat-connection-ack { color: #26af26; }
    .chat-message { border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; }
    #btnSend {
        background: #26af26; border: #26af26 1px solid; border-radius: 4px; color: #FFF;
        display: block; margin: 15px 0px; padding: 10px 50px; cursor: pointer;
    }
    #chat-box {
        background: #fff8f8; border: 1px solid #ffdddd; border-radius: 4px;
        border-bottom-left-radius: 0; border-bottom-right-radius: 0;
        min-height: 300px; padding: 10px; overflow: auto; white-space: pre-wrap; /* Preserve whitespace */
    }
    .chat-box-message {
        color: #09F; padding: 5px 10px; background-color: #fff;
        border: 1px solid #ffdddd; border-radius: 4px; display: block; /* Ensures each message appears on a new line */
        margin-bottom: 5px; /* Adds space between messages */
    }
    .chat-input { border: 1px solid #ffdddd; border-top: 0; width: 100%;
        box-sizing: border-box; padding: 10px 8px; color: #191919; }
</style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function showMessage(messageHTML) {
            $('#chat-box').append(messageHTML);
            $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight); // Auto-scroll to the bottom
        }

        $(document).ready(function() {
            var websocket = new WebSocket("ws://localhost:8080/demo/php-socket.php");

            websocket.onopen = function(event) {
                showMessage("<div class='chat-connection-ack'>Connection is established!</div>");
            };

            websocket.onmessage = function(event) {
                var Data = JSON.parse(event.data);
                showMessage("<div class='chat-box-message'>" + Data.username + ": " + Data.message + "</div>");
            };

            websocket.onerror = function(event) {
                showMessage("<div class='error'>Problem due to some Error</div>");
            };

            websocket.onclose = function(event) {
                showMessage("<div class='chat-connection-ack'>Connection Closed</div>");
            };

            $('#frmChat').on("submit", function(event) {
                event.preventDefault();
                var messageJSON = {
                    chat_user: $('#chat-user').val(),
                    chat_email: $('#chat-email').val(),
                    chat_message: $('#chat-message').val()
                };

                // Send data to the server to save to the database
                $.ajax({
                    url: 'save_chat.php', // This file will handle the saving of data
                    type: 'POST',
                    data: messageJSON,
                    success: function(response) {
                        console.log(response); // For debugging
                        websocket.send(JSON.stringify(messageJSON)); // Send to websocket after saving
                        $('#chat-message').val(''); // Clear the message input
                    },
                    error: function() {
                        showMessage("<div class='error'>Error sending message</div>");
                    }
                });
            });
        });
    </script>
</head>
<body>
    <form name="frmChat" id="frmChat">
        <div id="chat-box"></div>
        <input type="text" name="chat-user" id="chat-user" placeholder="Name" class="chat-input" required />
        <input type="email" name="chat-email" id="chat-email" placeholder="Email" class="chat-input" required />
        <input type="text" name="chat-message" id="chat-message" placeholder="Message" class="chat-input chat-message" required />
        <input type="submit" id="btnSend" name="send-chat-message" value="Send">
    </form>
</body>
</html>