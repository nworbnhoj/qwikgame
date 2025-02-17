import logging
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import render
from feedback.forms import FeedbackForm
from feedback.models import Feedback
from qwikgame.views import QwikView


logger = logging.getLogger(__file__)


class FeedbackListView(QwikView):
    feedback_form_class = FeedbackForm
    template_name = 'feedback/feedback_list.html'

    def context(self, request, *args, **kwargs):
        feedbacks = Feedback.objects.all()
        kwargs['items'] = feedbacks
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('feedback')

        context = super().context(request, *args, **kwargs)
        context |= {
            'feedback': context.get('item'),
            'feedbacks': context.get('items'),
        }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        feedback = context.get('feedback')
        if not feedback: 
            return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.feedback_form_class.post(request.POST)
        next = request.GET.get('next')
        form = context.get('feedback_form')
        if form and not form.is_valid():
            return HttpResponseRedirect(f'/feedback/')
        feedback = Feedback.objects.create(
        	path=next,
        	text=context['text'],
        )
        return HttpResponseRedirect(f'/feedback/{feedback.id}/')


class FeedbackView(FeedbackListView):
    template_name = 'feedback/feedback.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        pk = kwargs.get('feedback')
        if pk:
	        context |= {
	            'feedback': Feedback.objects.filter(pk=pk).first()
	        }
        self._context = context
        return self._context


    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        # feedback = context.get('feedback')
        # feedback.mark_seen([self.user.player.pk]).save()
        return render(request, self.template_name, context)

