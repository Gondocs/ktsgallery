# How to Compile Translations

To enable Hungarian translations for the KTS Gallery plugin, you need to compile the `.po` file to `.mo` format.

## Method 1: Using Poedit (Recommended - Easy)

1. Download and install **Poedit** from https://poedit.net/
2. Open Poedit
3. Click "File" → "Open" and select `languages/kts-gallery-hu_HU.po`
4. Click "File" → "Save" (or Ctrl+S)
5. Poedit will automatically create the `kts-gallery-hu_HU.mo` file

## Method 2: Using WordPress Plugin (Easy)

1. Install the "Loco Translate" plugin from WordPress
2. Go to **Loco Translate** → **Plugins** → **KTS Gallery**
3. It will automatically detect and compile your translations

## Method 3: Using Command Line (Advanced)

If you have gettext tools installed:

```bash
msgfmt languages/kts-gallery-hu_HU.po -o languages/kts-gallery-hu_HU.mo
```

## Method 4: Upload to WordPress Translation Service

1. Go to translate.wordpress.org
2. Submit your translations there
3. They will be automatically available in WordPress

## Testing

After compiling:

1. Go to WordPress **Settings** → **General**
2. Set **Site Language** to "Magyar" (Hungarian)
3. Visit your gallery - everything should now be in Hungarian!
4. Switch back to English to see English text

## The plugin will automatically:
- Show Hungarian when WordPress language is set to Hungarian (hu_HU)
- Show English when WordPress language is set to English (en_US) or any other language
