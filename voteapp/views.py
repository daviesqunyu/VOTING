from django.shortcuts import render

# Create your views here.

def index(request):
    context={}
    return render(request, 'voteapp/index.html', context  ) 

def detail(request):
    context={}
    return render(request, 'voteapp/detail.html', context  ) 

def result(request):
    context={}
    return render(request, 'voteapp/result.html', context  ) 

def signin(request):
    context={}
    return render(request, 'voteapp/signin.html', context  ) 

def signup(request):
    context={}
    return render(request, 'voteapp/signup.html', context  ) 
