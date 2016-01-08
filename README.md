# simple_inicis
Wordpress Inicis Plugin

워드프레스 이니시스 결제 연동 프로그램입니다.

# 필수 프로그램 ( Pre-requirement ) #

1. Woocommerce
2. Buddypress 또는 User Meta Manager
3. 회원가입 기능 필수 ( 가입시 입력되는 dispay_name = 이름, user_email = 이메일 가 결제시 넘어가는 buyername, buyeremail 이 됩니다 )
4. 전화번호 기능 필수 ( 전화번호 필드는 관리자 패널에서 선택이 가능합니다. )

# 사용법 #

우커머스에 올린 상품의 post_id 값이 3741 이라면

기본 
[wper_inicis woocommerce_product_id="3741"]	

사용자 설정 
[wper_inicis woocommerce_product_id="3741"]사용자 설정[/wper_inicis]	

결제 테스트 
[wper_inicis woocommerce_product_id="3741" item_name="Test" item_amount="1000"]결제 테스트[/wper_inicis]	

상품명과 상품 가격을 DB 참조없이 테스트 값으로 입력할 경우에는 item_name item_amount 에 따로 기입하면 됩니다.
