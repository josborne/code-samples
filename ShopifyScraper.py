#Shopify App Store Product Scraper
import os
import urllib
import base64
import requests
from bs4 import BeautifulSoup #html parser

IMAGE_LOC = 'c:\\temp\\ShopifyAppImages\\'
DOWNLOAD_IMAGE = False

class Product():
    id = -1
    name = 'NOT FOUND'
    price = 'NOT FOUND'
    developer = 'NOT FOUND'
    website = 'NOT FOUND'
    sellingPoints = 'NOT FOUND'
    desc = 'NOT FOUND'
    image = 'NOT FOUND'
    screenShots = []
    def __init__(self, id):
        self.id = id
    def output(self):
        string = str(self.id)+','+self.name+','+self.price+','+self.developer+','+self.website+','+self.sellingPoints+','+self.desc+','+self.image
        string += ','+','.join(self.screenShots)
        return string
    

def getProductPages():
    appPageLinks = []
    appPageImgs = []
    r = requests.get('http://apps.shopify.com/?filter=all');
    page = BeautifulSoup(r.text)
    appCards = page.find_all('li', class_='appcard col4 p30')
    for ac in appCards:
        appPageLinks.append('http://apps.shopify.com' + ac.find('a')['href'])
        #now get image
        bannerImgTag = ac.find('div', class_='appcard-content')
        if bannerImgTag != None:
            styleURL = bannerImgTag['style'].split('url(')[1] #e.g. background-image: url(//s3.amazonaws.com/shopify-app-store/shopify_applications/small_banners/260/splash.jpg?1337785175)
            imgURL = ''.join(styleURL.split())[:-1] #strip whitespace and drop last char )
            appPageImgs.append(imgURL)
        else:
            appPageImgs.append("NOT FOUND")
        
    return appPageLinks, appPageImgs
    
def getProductData(productID, pageLink):
    r = requests.get(pageLink)
    page = BeautifulSoup(r.text)
    product = Product(productID)
    product.name = getProductName(page)
    product.price, product.developer = getProductPriceAndDeveloper(page)
    product.website = getProductWebsite(page)
    product.sellingPoints = getProductSellingPoints(page)
    product.desc = getProductDesc(page)
    product.screenShots = getProductScreenShots(productID, page)
    return product
    
def getProductName(page):
    #only 1 h1 tag per page
    return page.find('h1').string
    
def getProductPriceAndDeveloper(page):
    price = 'NOT FOUND'
    developer = 'NOT FOUND'
    priceSection = page.find('div', class_='app-pricing')
    if priceSection == None:
        return price, developer
    rows = priceSection.find_all('tr')
    for r in rows:
        cells = r.find_all('td')
        if cells[0].string == 'Price':
            priceGen = cells[1].stripped_strings #sometimes price is like $99 - $299 per month
            price = ''
            for ps in priceGen:
                price += str(ps)
        elif cells[0].string == 'Developer':
            developer = cells[1].string
            
    return price, developer

    
def getProductWebsite(page):
    website = 'NOT FOUND'
    supportSection = page.find('div', class_='app-support')
    if supportSection == None:
        return website
    rows = supportSection.find_all('tr')
    for r in rows:
        cells = r.find_all('td')
        if cells[0].string == 'Website':
            w = cells[1].find('a')['href']
            if w != None:
                website = w
            
    return website
    
def getProductSellingPoints(page):
    sellingPoints = 'NOT FOUND'
    spSection = page.find('div', class_='app-selling-points')
    if spSection == None:
        return sellingPoints
    points = spSection.find_all('li')
    if len(points) > 0:
        sellingPoints = ''
    for p in points:
        sellingPoints += p.string + ':'
    #encode so they dont mess up CSV with commas in text
    sellingPoints = base64.b64encode(sellingPoints.encode('utf-8'))
    return sellingPoints
    
def getProductDesc(page):
    desc = 'NOT FOUND'
    descSection = page.find('div', class_='app-description')
    if descSection == None:
        return desc
    desc = base64.b64encode( descSection.prettify(formatter="html").encode('utf-8') )
    return desc
    
def getProductScreenShots(productID, page):
    screenShots = []
    spSection = page.find('div', class_='app-screenshots')
    if spSection == None:
        return ['NOT FOUND', 'NOT FOUND', 'NOT FOUND', 'NOT FOUND', 'NOT FOUND', 'NOT FOUND']
    images = spSection.find_all('a')
    ssCount = 1
    for i in images:
        imgURL = i['href'];
        fileName, ext = os.path.splitext(imgURL)
        if DOWNLOAD_IMAGE == True:
            urllib.urlretrieve('http:'+imgURL, IMAGE_LOC+str(productID)+"\\screenshot_orig"+str(ssCount)+ext)
        screenShots.append('/assets/img/'+str(productID)+'/screenshot_orig'+str(ssCount)+ext)
        
        thumbTag = i.find('img')
        if thumbTag != None:
            if thumbTag['alt'] == 'Large':
                thumbName = "screenshot_large"+str(ssCount)
            elif thumbTag['alt'] == 'Thumb':
                thumbName = "screenshot_thumb"+str(ssCount)
            else:
                thumbName = "screenshot"+str(ssCount)
                
            thumbURL = thumbTag['src'];
            fn, tExt = os.path.splitext(thumbURL)
            if DOWNLOAD_IMAGE == True:
                urllib.urlretrieve('http:'+thumbURL, IMAGE_LOC+str(productID)+"\\"+thumbName+tExt)
            screenShots.append('/assets/img/'+str(productID)+'/'+thumbName+tExt)
            ssCount += 1
            
    return screenShots

def getBannerImage(productID, imgURL):
    if imgURL != 'NOT FOUND':
        fileName, ext = os.path.splitext(imgURL)
        cleanExt = ext.split('?')[0]
        if DOWNLOAD_IMAGE == True:
            urllib.urlretrieve('http:'+imgURL, IMAGE_LOC+str(productID)+"\\banner_image"+cleanExt)
        return "/assets/img/"+str(productID)+'/banner_image'+cleanExt
    else:
        return imgURL
    
def main():
    #productList = []
    pages, bannerImages = getProductPages()
    productID = 1
    #create CSV
    outFile = open(IMAGE_LOC+"apps.csv",'w')
    outFile.write("id,name,price,developer,website,sellingPoints,desc,image,screenshot1,thumb1,screenshot2,thumb2,screenshot3,thumb3")
    outFile.write("\n")
    # get data
    for p in pages:
        if DOWNLOAD_IMAGE == True:
            os.makedirs(IMAGE_LOC+str(productID)) #create dir for product images
        newProduct = getProductData(productID, p)
        newProduct.image = getBannerImage(productID, bannerImages[productID-1])
        #productList.append(newProduct)
        try:
            outFile.write(newProduct.output())
        except:
            print "couldnt write product due to exception"        
        outFile.write("\n")
        productID += 1
        
    
    outFile.close()
    
if __name__ == "__main__":
    main()
    
    