from django import forms


class CargaCSVForm(forms.Form):
    archivo = forms.FileField(
        label="Archivo CSV",
        help_text="Selecciona un archivo CSV con los inmuebles."
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
