class Validation {

    // Validar o CPF informado

    cpfAlreadyInformed() {

        let state = false
        let cpf = $('#cpf').val()

        $.ajax({
            url: '/verificar-dados/cpf',
            type: 'GET',
            data: {
                cpf: cpf
            },
            async: false,
            dataType: 'json',
            success: data => data[0] != null ? state : state = true
        })

        return state
    }

    // Verificar o status do CPF

    cpfState(cpf) {

        let formattedCpf = cpf.replace(/[^\d]/g, "")
        let $cpfField = $('#cpfField')
        let message = '';

        $('.cpf-info').remove()

        if (formattedCpf.length == 11) {

            if (!this.cpfAlreadyInformed()) {

                $('#cpf').removeClass('is-valid')
                message = 'Cpf já cadastrado no sistema'

            } else {

                $('#cpf').addClass('is-valid')
            }

        } else {

            $('#cpf').removeClass('is-valid')
            message = 'Formato Inválido'
        }

        $($cpfField).append(`<small class="text-danger text-center cpf-info">${message}</small>`)

    }

    // Validar pelo total de caracteres 

    validateBySize(element, size, classField, elementInfo) {

        let formatElement = ($(`#${element}`).val()).replace(/[^\d]/g, "")

        if (formatElement.length == size) {

            $(`#${element}`).addClass('is-valid')
            $(`.${elementInfo}`).remove()

        } else {

            $(`#${element}`).removeClass('is-valid')
            $(`.${elementInfo}`).remove()
            $(classField).append(`<small class="text-danger text-center ${elementInfo} ">Formato Inválido</small>`)
        }

    }

    // Função para verificar se o campo possui um valor, caso sim, ele recebe a .class 'is-valid'

    validateByContent(element) {

        let $element = $(`#${element}`)

        $element.val() == '' ? $element.removeClass('is-valid') : $element.addClass('is-valid')

    }

    // Função para verificar se a imagem recebida é valida

    // A foto é opcional (sem foto o perfil usa a imagem padrão). Formatos e tamanho iguais aos do servidor
    // (App/Tools/Tools.php: JPG, PNG ou WebP até 5 MB). Com arquivo escolhido aparece "Remover foto".
    validateImage() {

        let photo = $('#profilePhoto')
        let file = photo.get(0).files[0]
        let message = ''

        $('.photo-info, .photo-remove').remove()
        photo.removeClass('is-valid')

        // sem arquivo: segue sem foto
        if (!file) return

        if (!/\.(jpe?g|png|webp)$/i.test(file.name)) {
            message = 'Formato inválido: use JPG, PNG ou WebP'
        } else if (file.size > 5 * 1024 * 1024) {
            message = 'A foto pode ter no máximo 5 MB'
        } else {
            photo.addClass('is-valid')
        }

        if (message) $('#photoField').append(`<small class="text-danger text-center photo-info">${message}</small>`)

        // tira o arquivo escolhido (inclusive o recusado) para seguir sem foto
        $('<button type="button" class="btn btn-link btn-sm photo-remove" style="min-height: 44px; margin-left: 8px;">Remover foto</button>')
            .on('click', () => {
                photo.val('')
                this.validateImage()
            })
            .appendTo('#photoField')
    }

    // Função para verificar se todos os campos do formulário foram preenchidos

    checkAllFields(form, totalFields, button) {

        let inputs = $(`${form} .form-control`)
        let photo = $('#profilePhoto')
        let name = $("#name").val().split(" ", 1)

        let size = null

        $('[containerRegistrationSuccess] , [containerRegistrationError]').hide()
        $("#name").val() == "" ? "" : $("[givenName]").text(`de ${name[0]}`)

        $.each(inputs, (key, value) => $(value).hasClass('is-valid') ? size += 1 : '')

            !photo.hasClass('is-valid') && $('.photo-info').length == 0 ? size += 1 : ''

        if (totalFields == size) {

            let accessCode = management.randCode()

            $(button).prop("disabled", false)

            $('[accessCode]').text('').append(accessCode)

            $('form #accessCode').val(accessCode)

            $('[containerRegistrationSuccess]').show()

        } else {

            $(button).prop("disabled", true)

            $('[containerRegistrationError]').show()

        }

    }

    // Função para verificar se o nome da avaliação já foi informada em outra na mesma unidade da disciplina

    checkRedundantName(route, form, button) {

        let $form = $(`${form}`).serialize()

        $.ajax({
            url: route,
            type: 'GET',
            data: $form,
            dataType: 'json',
            success: data => {

                if (data[0] != null) {

                    console.log(data)

                    tools.showToast('Nome já vinculado a uma avaliação nessa unidade', 'bg-info', 6000)

                    $(button).addClass('disabled')

                } else {

                    $(button).removeClass('disabled')
                }

            }
        })

    }

    // Função para verificar se o formato do e-mail é válido

    checkEmail(email) {

        let result = /\S+@\S+\.\S+/
        let message = ''

        $('.email-info').remove()

        if (!result.test(email)) {

            message = "Formato inválido"

            $("#email").removeClass('is-valid')

            $('#emailField').append(`<small class="text-danger text-center email-info">${message}</small>`)

        } else {

            $('.email-info').remove()

            $("#email").addClass('is-valid')

        }
    }

}