from django import forms
from django.contrib.auth import get_user_model
from django.contrib.auth.forms import AuthenticationForm
from django.contrib.auth.models import Group
from django.contrib.auth.password_validation import validate_password
from django.core.exceptions import ValidationError

from .roles import GRUPO_CLIENTE, crear_grupos_y_permisos

User = get_user_model()

MENSAJE_LOGIN_INVALIDO = "Correo o contraseña incorrectos."


class CargaCSVForm(forms.Form):
    archivo = forms.FileField(
        label="Archivo CSV",
        help_text="Selecciona un archivo CSV con los inmuebles.",
        widget=forms.ClearableFileInput(
            attrs={"class": "form-control", "accept": ".csv"}
        ),
    )

    def clean_archivo(self):
        archivo = self.cleaned_data["archivo"]

        if not archivo.name.lower().endswith(".csv"):
            raise forms.ValidationError(
                "El archivo debe tener formato CSV."
            )

        if archivo.size > 2 * 1024 * 1024:
            raise forms.ValidationError(
                "El archivo no puede superar los 2 MB."
            )

        return archivo


class InicioSesionForm(AuthenticationForm):
    """
    Formulario de login con correo y contraseña.

    Siempre muestra el mismo mensaje cuando algo falla, para no revelar si
    el correo existe o si lo que estaba mal era la contraseña.
    """

    username = forms.CharField(
        label="Correo electrónico",
        widget=forms.TextInput(
            attrs={
                "autofocus": True,
                "autocomplete": "username",
                "class": "form-control",
                "placeholder": "ejemplo@gmail.com",
            }
        ),
    )
    password = forms.CharField(
        label="Contraseña",
        strip=False,
        widget=forms.PasswordInput(
            attrs={
                "autocomplete": "current-password",
                "class": "form-control",
                "placeholder": "********",
            }
        ),
    )

    error_messages = {
        "invalid_login": MENSAJE_LOGIN_INVALIDO,
        # Misma frase para una cuenta desactivada: no se revela su estado.
        "inactive": MENSAJE_LOGIN_INVALIDO,
    }

    def clean_username(self):
        usuario = self.cleaned_data["username"].strip()

        # Los correos se guardan en minúsculas al registrarse.
        if "@" in usuario:
            usuario = usuario.lower()

        return usuario


class RegistroClienteForm(forms.Form):
    """Registro público: solo crea cuentas con rol Cliente."""

    nombre = forms.CharField(
        label="Nombre",
        max_length=150,
        widget=forms.TextInput(
            attrs={"class": "form-control", "placeholder": "Tu nombre"}
        ),
        error_messages={"required": "El nombre es obligatorio."},
    )
    correo = forms.EmailField(
        label="Correo electrónico",
        max_length=150,
        widget=forms.EmailInput(
            attrs={"class": "form-control", "placeholder": "ejemplo@gmail.com"}
        ),
        error_messages={
            "required": "El correo electrónico es obligatorio.",
            "invalid": "Ingresa un correo electrónico válido.",
        },
    )
    password1 = forms.CharField(
        label="Contraseña",
        strip=False,
        widget=forms.PasswordInput(
            attrs={
                "autocomplete": "new-password",
                "class": "form-control",
                "placeholder": "********",
            }
        ),
        error_messages={"required": "La contraseña es obligatoria."},
    )
    password2 = forms.CharField(
        label="Confirmar contraseña",
        strip=False,
        widget=forms.PasswordInput(
            attrs={
                "autocomplete": "new-password",
                "class": "form-control",
                "placeholder": "********",
            }
        ),
        error_messages={"required": "Confirma la contraseña."},
    )

    def clean_nombre(self):
        nombre = self.cleaned_data["nombre"].strip()

        if not nombre:
            raise ValidationError("El nombre es obligatorio.")

        return nombre

    def clean_correo(self):
        correo = self.cleaned_data["correo"].strip().lower()

        if (
            User.objects.filter(username__iexact=correo).exists()
            or User.objects.filter(email__iexact=correo).exists()
        ):
            raise ValidationError(
                "Ya existe una cuenta registrada con este correo."
            )

        return correo

    def clean(self):
        cleaned_data = super().clean()

        password1 = cleaned_data.get("password1")
        password2 = cleaned_data.get("password2")

        if password1 and password2 and password1 != password2:
            self.add_error("password2", "Las contraseñas no coinciden.")

        elif password1:
            # Aplica los validadores de AUTH_PASSWORD_VALIDATORS
            # (longitud mínima, contraseñas comunes, solo números...).
            usuario_temporal = User(
                username=cleaned_data.get("correo", ""),
                email=cleaned_data.get("correo", ""),
                first_name=cleaned_data.get("nombre", ""),
            )
            try:
                validate_password(password1, usuario_temporal)
            except ValidationError as error:
                self.add_error("password1", error)

        return cleaned_data

    def save(self):
        correo = self.cleaned_data["correo"]

        # La contraseña se guarda cifrada (hash), nunca en texto plano.
        usuario = User.objects.create_user(
            username=correo,
            email=correo,
            password=self.cleaned_data["password1"],
            first_name=self.cleaned_data["nombre"],
        )

        grupo = Group.objects.filter(name=GRUPO_CLIENTE).first()
        if grupo is None:
            crear_grupos_y_permisos()
            grupo = Group.objects.get(name=GRUPO_CLIENTE)

        usuario.groups.add(grupo)

        return usuario
