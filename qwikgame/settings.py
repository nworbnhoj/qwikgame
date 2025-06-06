import django, logging, os, socket
from dotenv import load_dotenv
from pathlib import Path

logger = logging.getLogger(__file__)
if not load_dotenv():
    logger.warn("Missing .env")

# Build paths inside the project like this: BASE_DIR / 'subdir'.
BASE_DIR = Path(__file__).resolve().parent.parent


# Quick-start development settings - unsuitable for production
# See https://docs.djangoproject.com/en/5.0/howto/deployment/checklist/

# SECURITY WARNING: keep the secret key used in production secret!
SECRET_KEY = os.getenv('DJANGO_SECRET_KEY','error: set environ variable DJANGO_SECRET_KEY')

# SECURITY WARNING: don't run with debug turned on in production!
DEBUG = True

FQDN = socket.getfqdn()

ALLOWED_HOSTS = [
    'localhost',
    'alpha.qwikgame.org',
    'beta.qwikgame.org',
    'www.qwikgame.org',
]
CSRF_TRUSTED_ORIGINS = [
    "https://alpha.qwikgame.org",
    "https://beta.qwikgame.org",
    "https://www.qwikgame.org",
]
CSRF_ALLOWED_ORIGINS = [
    "https://alpha.qwikgame.org",
    "https://beta.qwikgame.org",
    "https://www.qwikgame.org",
]
CORS_ORIGINS_WHITELIST = [
    "https://alpha.qwikgame.org",
    "https://beta.qwikgame.org",
    "https://www.qwikgame.org",
]


# Application definition

INSTALLED_APPS = [
    'appeal.apps.AppealConfig',
    'api.apps.ApiConfig',
    'authenticate.apps.AuthenticateConfig',
    'game.apps.GameConfig',
    'feedback.apps.FeedbackConfig',
    'person.apps.PersonConfig',
    'player.apps.PlayerConfig',
    'service.apps.ServiceConfig',
    'venue.apps.VenueConfig',
    'responsive.conf.ResponsiveAppConf',
    'django.contrib.admin',
    'django.contrib.auth',
    'django.contrib.contenttypes',
    'django.contrib.sessions',
    'django.contrib.messages',
    'django.contrib.staticfiles',
    'django_crontab',
    'webpush',
]

# Custom User Model
AUTH_USER_MODEL = "authenticate.User"
LOGIN_URL = "/authenticate/login"
LOGIN_REDIRECT_URL = "/appeal"
LOGOUT_REDIRECT_URL = "/"
# timeout seconds for login / registration / invitation links
PASSWORD_RESET_TIMEOUT = 60 * 60 * 24 * 2

MIDDLEWARE = [
    'django.middleware.security.SecurityMiddleware',
    'django.contrib.sessions.middleware.SessionMiddleware',
    'django.middleware.common.CommonMiddleware',
    'django.middleware.csrf.CsrfViewMiddleware',
    'django.middleware.locale.LocaleMiddleware',
    'django.contrib.auth.middleware.AuthenticationMiddleware',
    'django.contrib.messages.middleware.MessageMiddleware',
    'django.middleware.clickjacking.XFrameOptionsMiddleware',
    'responsive.middleware.ResponsiveMiddleware',
]

ROOT_URLCONF = 'qwikgame.urls'

TEMPLATES = [
    {
        'BACKEND': 'django.template.backends.django.DjangoTemplates',
        'DIRS': [
            BASE_DIR / 'templates', 
            django.__path__[0] + "/forms/templates",
        ],
        'APP_DIRS': True,
        'OPTIONS': {
            'context_processors': [
                'django.template.context_processors.debug',
                'django.template.context_processors.request',
                'django.contrib.auth.context_processors.auth',
                'django.contrib.messages.context_processors.messages',
                'responsive.context_processors.device',
            ],
        },
    },
]

FORM_RENDERER = 'django.forms.renderers.TemplatesSetting'

WSGI_APPLICATION = 'qwikgame.wsgi.application'


# Database
# https://docs.djangoproject.com/en/5.0/ref/settings/#databases

DATABASES = {
    'default': {
        'ENGINE': os.getenv('DATABASE_ENGINE','error: set environ variable DATABASE_ENGINE'),
        'NAME': os.getenv('DATABASE_NAME','error: set environ variable DATABASE_NAME'),
        'USER': os.getenv('DATABASE_USER','error: set environ variable DATABASE_USER'),
        'PASSWORD': os.getenv('DATABASE_PASSWORD','error: set environ variable DATABASE_PASSWORD'),
        'HOST': os.getenv('DATABASE_HOST','error: set environ variable DATABASE_HOST'),
        'PORT': os.getenv('DATABASE_PORT','error: set environ variable DATABASE_PORT'),
    }
}





# Password validation
# https://docs.djangoproject.com/en/5.0/ref/settings/#auth-password-validators

AUTH_PASSWORD_VALIDATORS = [
    {
        'NAME': 'django.contrib.auth.password_validation.UserAttributeSimilarityValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.MinimumLengthValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.CommonPasswordValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.NumericPasswordValidator',
    },
]

# https://web-push-codelab.glitch.me/
WEBPUSH_SETTINGS = {
   "VAPID_PUBLIC_KEY":  os.getenv('VAPID_PUBLIC_KEY','error: set environ variable VAPID_PUBLIC_KEY'),
   "VAPID_PRIVATE_KEY":  os.getenv('VAPID_PRIVATE_KEY','error: set environ variable VAPID_PRIVATE_KEY'),
   "VAPID_ADMIN_EMAIL":  os.getenv('VAPID_ADMIN_EMAIL','error: set environ variable VAPID_ADMIN_EMAIL'),
}


# Internationalization
# https://docs.djangoproject.com/en/5.0/topics/i18n/

LANGUAGE_CODE = 'en-us'

TIME_ZONE = 'Australia/Melbourne'

USE_I18N = True

USE_TZ = True


# Static files (CSS, JavaScript, Images)
# https://docs.djangoproject.com/en/5.0/howto/static-files/

STATIC_URL = 'static/'
STATIC_ROOT = BASE_DIR / 'static'


STATICFILES_DIRS = [
    BASE_DIR / 'qwikgame/static'
]

CRONJOBS = [
    ('1 * * * *', 'appeal.cron.appeal_perish', '>> /var/log/django_cron_alpha.log'),
    ('10 * * * *', 'appeal.cron.bid_perish', '>> /var/log/django_cron_alpha.log'),
    ('15 * * * *', 'game.cron.match_review_init', '>> /var/log/django_cron_alpha.log'),
    ('50 * * * *', 'game.cron.match_perish', '>> /var/log/django_cron_alpha.log'),
    ('51 * * * *', 'game.cron.match_review_perish', '>> /var/log/django_cron_alpha.log'),
]


# Default primary key field type
# https://docs.djangoproject.com/en/5.0/ref/settings/#default-auto-field

DEFAULT_AUTO_FIELD = 'django.db.models.BigAutoField'

LOGGING = {
    "version": 1,
    "disable_existing_loggers": False,
    "root": {"level": "INFO", "handlers": ["file"]},
    "handlers": {
        "file": {
            "level": "INFO",
            "class": "logging.FileHandler",
            "filename": "/var/log/django_alpha.log",
            "formatter": "app",
        },
    },
    "loggers": {
        "django": {
            "handlers": ["file"],
            "level": "INFO",
            "propagate": False
        },
    },
    "formatters": {
        "app": {
            'format': '%(asctime)s %(levelname)-8s %(name)-12s %(message)s',
            "datefmt": "%Y-%m-%d %H:%M:%S",
        },
    },
}



"""
While there are several different items we can query on,
the ones used for django-responsive2 are min-width, max-width, min-height and max-height.

min_width -- Rules applied for any device width over the value defined in the config.
max_width -- Rules applied for any device width under the value defined in the config.
min_height -- Rules applied for any device height over the value defined in the config.
max_height -- Rules applied for any device height under the value defined in the config.
pixel_ratio -- Rules applied for any device with devicePixelRatio defined in the config.

Usage
------
    {% load 'responsive' %}

    {% renderblockif 'small' 'medium' %}
        [...]
    {% endrenderblockif %}

"""
RESPONSIVE_CACHE_PREFIX = 'responsive_'
RESPONSIVE_CACHE_DURATION = 60 * 60 * 24 * 356  # 1 year
RESPONSIVE_COOKIE_NAME = 'clientinfo'
RESPONSIVE_COOKIE_AGE = 365  # days
RESPONSIVE_DEFAULT_HEIGHT = 0
RESPONSIVE_DEFAULT_WIDTH = 0
RESPONSIVE_DEFAULT_PIXEL_RATIO = 1
# Borrowed from ZURB Foundation framework.
# See http://foundation.zurb.com/docs/media-queries.html
RESPONSIVE_MEDIA_QUERIES = {
    'small': {
        # 'verbose_name': _('Small screens'),
        'min_width': None,
        'max_width': 640,
    },
    'medium': {
        # 'verbose_name': _('Medium screens'),
        'min_width': 641,
        'max_width': 1024,
    },
    'large': {
        # 'verbose_name': _('Large screens'),
        'min_width': 1025,
        'max_width': 1440,
    },
    'xlarge': {
        # 'verbose_name': _('XLarge screens'),
        'min_width': 1441,
        'max_width': 1920,
    },
    'xxlarge': {
        # 'verbose_name': _('XXLarge screens'),
        'min_width': 1921,
        'max_width': None,
    }
}
RESPONSIVE_VARIABLE_NAME = 'device'

# Email Settings
EMAIL_BACKEND = os.getenv('EMAIL_BACKEND','django.core.mail.backends.console.EmailBackend')
EMAIL_HOST = os.getenv('EMAIL_HOST','error: set environ variable EMAIL_HOST')
EMAIL_PORT = os.getenv('EMAIL_PORT','587')
EMAIL_USE_TLS = os.getenv('EMAIL_USE_TLS','error: set environ variable EMAIL_USE_TLS') == "True"
EMAIL_HOST_USER = os.getenv('EMAIL_HOST_USER','error: set environ variable EMAIL_HOST_USER')
EMAIL_HOST_PASSWORD = os.getenv('EMAIL_HOST_PASSWORD','error: set environ variable EMAIL_HOST_PASSWORD')
