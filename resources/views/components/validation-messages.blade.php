{{-- Translated texts for resources/js/form-validation.js (front-end validation). The server has its own messages. --}}
<script>
    window.validationMessages = @js([
        'required' => __('This field is required – please complete it.'),
        'email' => __('Enter a valid email address.'),
        'minlength' => __('Use at least :min characters.'),
        'maxlength' => __('Use at most :max characters.'),
        'pattern' => __('This value is not in the expected format.'),
        'strong' => __('The password needs 10+ characters, upper and lower case letters, a number and a symbol.'),
        'match' => __('The passwords do not match.'),
    ]);
</script>
