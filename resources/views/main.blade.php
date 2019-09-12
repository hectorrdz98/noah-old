@extends('layouts.home')

@section('content')

<section class="hero is-fullheight">
    <div class="hero-body">
        <div class="container">
            <h1 class="title" style="text-align: center;">
                Inteligencia Artificial "Noah"
            </h1>
            <div class="field is-horizontal question-area">
                <div class="divLabel">
                    <label class="label">Pregunta</label>
                </div>
                <div>
                    <div class="field has-addons">
                        <p class="control">
                            <input id="questionInput" class="input" type="text" placeholder="Ingresa tu pregunta...">
                        </p>
                        <div class="control">
                            <a id="questionGo" class="button is-info">GO!</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal chat-area">
                <section id="chat-hero" class="hero is-light">
                    <div class="hero-body">
                        <div id="chat-container" class="container"></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>

<script>

$("#questionGo").click(function() {
    var question = $("#questionInput").val();
    if (question != "") {
        var questionHTML = `
            <h2 class="subtitle is-question">
                ` + question + `
            </h2>
        `;
        $("#chat-container").append(questionHTML);

        var formData = new FormData();
        formData.append("question", question);
        $.ajax({
            url: "{{ route('question.newQuestion') }}",
            type: "POST",
            data: formData,
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
                chat(data);
            }
        });
    }
});

function chat(data) {
    var answerHTML = `
        <h2 class="subtitle">
            ` + data + `
        </h2>
    `;
    $("#chat-container").append(answerHTML);
    $('#chat-hero').animate({
        scrollTop: $('#chat-hero')[0].scrollHeight
    }, 1000);
}

</script>

@endsection